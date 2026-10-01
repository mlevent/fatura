<?php

declare(strict_types=1);

namespace Mlevent\Fatura;

use GuzzleHttp\Cookie\CookieJar;
use Mlevent\Fatura\Enums\DocumentType;
use Mlevent\Fatura\Enums\ObjectionMethod;
use Mlevent\Fatura\Enums\Type;
use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\BadResponseException;
use Mlevent\Fatura\Exceptions\DraftCorrelationException;
use Mlevent\Fatura\Exceptions\DraftPreflightException;
use Mlevent\Fatura\Exceptions\InvalidArgumentException;
use Mlevent\Fatura\Exceptions\InvalidFormatException;
use Mlevent\Fatura\Exceptions\SessionExpiredException;
use Mlevent\Fatura\Interfaces\ModelInterface;
use Mlevent\Fatura\Models\UserDataModel;
use Mlevent\Fatura\Utils\DraftMatcher;
use Ramsey\Uuid\Uuid;

class Gib
{    
    /**
     * Api
     */
    protected const API = [
        'gateways' => [
            'prod' => 'https://earsivportal.efatura.gov.tr',
            'test' => 'https://earsivportaltest.efatura.gov.tr',
        ],
        'paths' => [
            'esign'    => '/earsiv-services/esign',
            'login'    => '/earsiv-services/assos-login',
            'dispatch' => '/earsiv-services/dispatch',
            'download' => '/earsiv-services/download',
        ]
    ];

    /**
     * @var array
     */
    protected array $column  = [];
    protected array $filters = [];
    protected array $limit   = [];

    /**
     * @var string
     */
    protected string $lastId = '';
    
    /**
     * @var integer
     */
    protected int $rowCount = 0;

    /**
     * @var boolean
     */
    protected bool $sortByDesc = false;

    protected ?CookieJar $cookieJar = null;
    protected bool $portalReady = false;

    /**
     * @var string|array
     */
    protected string|array $uuid;

    /**
     * __construct
     *
     * @param DocumentType $documentType
     * @param boolean      $testMode
     * @param string|null  $username
     * @param string|null  $password
     * @param string|null  $token
     */
    public function __construct(
        protected DocumentType $documentType = DocumentType::Invoice,
        protected bool         $testMode     = false,
        protected ?string      $username     = null,
        protected ?string      $password     = null,
        protected ?string      $token        = null,
    ) {}

    /**
     * testMode
     */
    public function testMode(): self
    {
        $this->testMode = true;
        return $this;
    }
    
    /**
     * setCredentials
     */
    public function setCredentials(?string $username = null, ?string $password = null): self
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }
    
    /**
     * getCredentials
     */
    public function getCredentials(): array
    {
        return [
            'username' => $this->username, 
            'password' => $this->password
        ];
    }
    
    /**
     * setTestCredentials
     */
    public function setTestCredentials(?string $username = null, ?string $password = null): self
    {   
        if ($username && $password) {
            return $this->testMode()->setCredentials($username, $password);
        }
        return $this->testMode()->setCredentials(...$this->getTestCredentials());
    }

    /**
     * getTestCredentials
     */
    public function getTestCredentials(): array
    {
        $response = $this->request($this->getGateway('esign'), [
            'assoscmd' => 'kullaniciOner',
            'rtype'    => 'json',
        ]);
        if (!$response->get('userid')) {
            throw new ApiException('Şu anda sistemdeki tüm test hesapları kullanılıyor.');
        }
        return ['username' => $response->get('userid'), 'password' => '1'];
    }
    
    /**
     * setToken
     */
    public function setToken(?string $token = null): self
    {
        if ($this->token !== $token) {
            $this->cookieJar = null;
            $this->portalReady = false;
        }
        $this->token = $token;
        return $this;
    }

    /**
     * getToken
     */
    public function getToken(): string
    {
        return $this->token ?? '';
    }

    /**
     * setUuid
     */
    protected function setUuid(string|array $uuid): string|array
    {
        array_map(function ($id) {
            if (!Uuid::isValid($id)) {
                throw new InvalidArgumentException('Uuid doğrulanamadı.', $id);
            }
        }, (array) $uuid);

        return $this->uuid = $uuid;
    }

    /**
     * getUuid
     */
    protected function getUuid(): string|array
    {
        return $this->uuid;
    }
        
    /**
     * login
     */
    public function login(?string $username = null, ?string $password = null): self
    {
        if ($username && $password) {
            $this->setCredentials($username, $password);
        }

        $this->cookieJar = new CookieJar();
        $this->portalReady = false;
        $this->token = null;
        try {
            $response = $this->request($this->getGateway('login'), [
                'assoscmd' => $this->testMode ? 'login' : 'anologin',
                'userid'   => $this->username,
                'sifre'    => $this->password,
                'sifre2'   => $this->password,
                'parola'   => $this->password,
            ]);
            $token = $response->get('token');
            if (!is_string($token) || $token === '') {
                throw new ApiException('GİB giriş yanıtında token bulunamadı.', null, $response->get());
            }
            // Giriş yanıtının çerezleri index.jsp isteğinde de kullanılmalı.
            $this->token = $token;
            $this->bootPortalSession();
        } catch (\Throwable $e) {
            $this->setToken();
            $this->cookieJar = null;
            throw $e;
        }
        return $this;
    }
    
    /**
     * logout
     */
    public function logout(): bool
    {
        try {
            $this->request($this->getGateway('login'), ['assoscmd' => 'logout', 'token' => $this->token]);
        } finally {
            $this->setCredentials();
            $this->setToken();
            $this->cookieJar = null;
            $this->portalReady = false;
        }
        return true;
    }
        
    /**
     * getRecipientData
     */
    public function getRecipientData(string $taxOrTrId): array
    {
        $response = $this->dispatchRead(['SICIL_VEYA_MERNISTEN_BILGILERI_GETIR', 'RG_BASITFATURA'], ['vknTcknn' => $taxOrTrId]);
        return $response->get('data');
    }
        
    /**
     * getUserData
     */
    public function getUserData(): array
    {
        $response = $this->dispatchRead(['EARSIV_PORTAL_KULLANICI_BILGILERI_GETIR', 'RG_KULLANICI']);
        return $response->get('data');
    }
    
    /**
     * updateUserData
     */
    public function updateUserData(UserDataModel|array $userData): bool
    {
        $userData = $userData instanceof UserDataModel 
            ? $userData->export() 
            : $userData;

        $response = $this->request($this->getGateway('dispatch'),
            $this->setParams(['EARSIV_PORTAL_KULLANICI_BILGILERI_KAYDET', 'RG_KULLANICI'], $userData)
        );
        return $response->get('data') ? true : false;
    }

    /**
     * Portalda kayıtlı GSM numarası
     */
    public function getPhoneNumber(): ?string
    {
        $response = $this->dispatchRead(['EARSIV_PORTAL_TELEFONNO_SORGULA', 'RG_BASITTASLAKLAR']);
        return $response->object('data')->telefon ?? null;
    }

    /**
     * Belge imzalamak için gerekli Operasyon ID bilgisi
     */
    public function startSmsVerification(): ?string
    {
        if (!$phoneNumber = $this->getPhoneNumber()) {
            return null;
        }
        $response = $this->request($this->getGateway('dispatch'),
            $this->setParams(['EARSIV_PORTAL_SMSSIFRE_GONDER', 'RG_SMSONAY'], [
                'CEPTEL'  => $phoneNumber, 
                'KCEPTEL' => false, 
                'TIP'     => ''
            ])
        );
        return $response->object('data')->oid ?? null;
    }

    /**
     * completeSmsVerification
     */
    public function completeSmsVerification(string $code, string $oid, array $documents): bool
    {
        $setToSign = array_map(function ($uuid) {
            return [
                'belgeTuru' => $this->documentType->value,
                'ettn'      => $uuid,
            ];
        }, $this->setUuid($documents));

        $response = $this->request($this->getGateway('dispatch'),
            $this->setParams(['0lhozfib5410mp', 'RG_SMSONAY'], [
                'DATA'  => $setToSign,
                'SIFRE' => $code, 
                'OID'   => $oid, 
                'OPR'   => 1, 
            ]), true, ['timeout' => 300]
        );
        $data = $response->get('data');
        if (is_array($data) && in_array($data['sonuc'] ?? null, ['1', 1], true)) {
            $this->setRowCount(sizeof($documents));
            return true;
        }
        return false;
    }

    /**
     * createDraft
     */
    public function createDraft(ModelInterface|array $data): bool
    {
        $model = $data instanceof ModelInterface ? $data : null;
        $data = $model ? $model->export() : $data;
        $isNewDraft = empty($data['belgeNumarasi']);
        $this->setLastId('');
        $lock = null;
        $known = [];
        if ($isNewDraft) {
            $model?->setUuid('');
            unset($data['uuid'], $data['faturaUuid'], $data['ettn']);
            try {
                $date = $this->draftDate($data);
                if (DraftMatcher::matches($data, $data, $this->documentType, '') !== true) {
                    throw new DraftPreflightException('ETTN eşleştirmesi için alıcı, tarih ve tutar bilgileri gerekli.');
                }
                $lock = $this->acquireDraftCreationLock();
                $known = $this->draftUuids($this->listDocumentSummaries($date, $date));
            } catch (\Throwable $e) {
                if (is_resource($lock)) fclose($lock);
                throw new DraftPreflightException('Taslak ön kontrolü tamamlanamadı; oluşturma isteği gönderilmedi.', null, null, 0, $e instanceof \Exception ? $e : null);
            }
        }
        
        $requestPath = match ($this->documentType) {
            DocumentType::Invoice             => ['EARSIV_PORTAL_FATURA_OLUSTUR', 'RG_BASITFATURA'],
            DocumentType::ProducerReceipt     => ['EARSIV_PORTAL_MUSTAHSIL_OLUSTUR', 'RG_MUSTAHSIL'],
            DocumentType::SelfEmployedReceipt => ['EARSIV_PORTAL_SERBEST_MESLEK_MAKBUZU_OLUSTUR', 'RG_SERBEST'],
        };

        try {
            $response = $this->request($this->getGateway('dispatch'), $this->setParams($requestPath, $data));
            $result = $response->get('data');
            $returnedUuid = $this->draftResponseUuid($result);
            $success = is_string($result) && str_contains($result, 'başarıyla');
            if (is_array($result)) {
                foreach (['success', 'sonuc'] as $key) {
                    if (array_key_exists($key, $result) && !in_array($result[$key], [true, 1, '1'], true)) {
                        throw new DraftCorrelationException('GİB taslak yanıtı başarı bildirmiyor. Yeniden göndermeden önce portalı kontrol edin.', $data, $response->get());
                    }
                }
                $success = ($result['success'] ?? false) === true || in_array($result['sonuc'] ?? null, [1, '1'], true);
            }
            if (!$success && $returnedUuid === '') {
                throw new DraftCorrelationException('GİB taslak yanıtı doğrulanamadı. Yeniden göndermeden önce portalı kontrol edin.', $data, $response->get());
            }
            $uuid = $isNewDraft
                ? $this->resolveNewDraftUuid($data, $known, $returnedUuid)
                : ($model?->getUuid() ?: ($data['faturaUuid'] ?? $data['ettn'] ?? $data['uuid'] ?? ''));
            $this->setLastId($uuid);
            if ($isNewDraft) $model?->setUuid($uuid);
            return true;
        } catch (BadResponseException $e) {
            throw new DraftCorrelationException('GİB taslak isteğinin sonucu alınamadı. Aynı belgeyi tekrar göndermeden önce portalı kontrol edin.', null, null, 0, $e);
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    protected function draftDate(array $data): string
    {
        $date = $data[$this->documentType === DocumentType::Invoice ? 'faturaTarihi' : 'tarih'] ?? '';
        if (!is_string($date)) throw new InvalidFormatException('Tarih geçerli formatta değil.');
        $this->parseDocumentDate($date);
        return $date;
    }

    protected function acquireDraftCreationLock(): mixed
    {
        $key = hash('sha256', $this->getGateway('dispatch') . ':' . ($this->username ?: $this->token));
        $lock = @fopen(sys_get_temp_dir() . '/mlevent-fatura-' . $key . '.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) fclose($lock);
            throw new DraftPreflightException('Bu hesap için başka bir taslak oluşturma işlemi sürüyor.');
        }
        return $lock;
    }

    protected function draftResponseUuid(mixed $result): string
    {
        if (is_string($result) && Uuid::isValid(trim($result))) return trim($result);
        if (is_array($result)) {
            $uuids = [];
            foreach (['ettn', 'faturaUuid', 'uuid'] as $key) {
                if (!empty($result[$key]) && (!is_string($result[$key]) || !Uuid::isValid($result[$key]))) {
                    throw new DraftCorrelationException('GİB taslak yanıtında geçersiz ETTN var.');
                }
                if (is_string($result[$key] ?? null) && Uuid::isValid($result[$key])) $uuids[strtolower($result[$key])] = true;
            }
            if (count($uuids) > 1) throw new DraftCorrelationException('GİB birbiriyle çelişen taslak ETTN bilgileri döndürdü.');
            return array_key_first($uuids) ?? '';
        }
        return '';
    }

    protected function draftUuids(array $summaries): array
    {
        $uuids = [];
        foreach ($summaries as $summary) {
            $uuid = $summary['ettn'] ?? null;
            if (!is_string($uuid) || !Uuid::isValid($uuid) || isset($uuids[strtolower($uuid)])) {
                throw new ApiException('GİB taslak listesinde geçersiz veya tekrarlanan ETTN var.');
            }
            $uuids[strtolower($uuid)] = $summary;
        }
        return $uuids;
    }

    protected function resolveNewDraftUuid(array $data, array $known, string $returnedUuid): string
    {
        $date = $this->draftDate($data);
        if ($returnedUuid !== '' && isset($known[strtolower($returnedUuid)])) {
            throw new DraftCorrelationException('GİB yeni taslak için mevcut bir belgenin ETTN bilgisini döndürdü.');
        }
        $previous = '';
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->waitForDraftVisibility($attempt);
            try {
                $candidates = $returnedUuid !== '' ? [$returnedUuid => []]
                    : array_diff_key($this->draftUuids($this->listDocumentSummaries($date, $date)), $known);
                $matches = [];
                $unresolved = false;
                foreach ($candidates as $uuid => $summary) {
                    if (isset($summary['belgeTuru']) && $summary['belgeTuru'] !== $this->documentType->value) continue;
                    try {
                        $match = DraftMatcher::matches($this->getDocument($uuid), $data, $this->documentType, $uuid);
                    } catch (ApiException|BadResponseException) {
                        $match = null;
                    }
                    if ($match === true) $matches[] = $uuid;
                    elseif ($match === null) $unresolved = true;
                }
            } catch (ApiException|BadResponseException) {
                $previous = '';
                continue;
            }
            if (count($matches) > 1) {
                throw new DraftCorrelationException('Birden fazla yeni taslak aynı verilerle eşleşiyor. Yeniden göndermeden önce portalı kontrol edin.');
            }
            $candidate = !$unresolved && count($matches) === 1 ? $matches[0] : '';
            if ($candidate !== '' && ($returnedUuid !== '' || $candidate === $previous)) return $candidate;
            $previous = $candidate;
        }
        throw new DraftCorrelationException('Taslak gönderildi ancak ETTN güvenle eşleştirilemedi. Aynı belgeyi tekrar göndermeyin; portalı kontrol edin.');
    }

    protected function waitForDraftVisibility(int $attempt): void
    {
        if ($attempt > 0) usleep(500000);
    }

    /**
     * deleteDraft
     */
    public function deleteDraft(array $documents, string $reason = 'Hatalı İşlem'): bool
    {
        $setToDelete = array_map(function ($uuid) {
            return [
                'belgeTuru' => $this->documentType->value,
                'ettn'      => $uuid,
            ];
        }, $this->setUuid($documents));

        $response = $this->request($this->getGateway('dispatch'),
            $this->setParams(['EARSIV_PORTAL_FATURA_SIL', 'RG_TASLAKLAR'], [
                'silinecekler' => $setToDelete, 
                'aciklama'     => $reason,
            ])
        );
        if (preg_match('/(\d+)/', $response->get('data'), $affectedRow)) {
            $this->setRowCount((int)$affectedRow[1]); 
            return true;
        }
        return false;
    }

    /**
     * getDocument
     */
    public function getDocument(string $uuid): array
    {
        $uuid = $this->setUuid($uuid);
        $paths = match ($this->documentType) {
            DocumentType::Invoice             => [['EARSIV_PORTAL_FATURA_GETIR', 'RG_BASITFATURA'], ['EARSIV_PORTAL_FATURA_GETIR', 'RG_TASLAKLAR']],
            DocumentType::ProducerReceipt     => [['EARSIV_PORTAL_MUSTAHSIL_GETIR', 'RG_MUSTAHSIL']],
            DocumentType::SelfEmployedReceipt => [['EARSIV_PORTAL_SERBEST_MESLEK_GETIR', 'RG_SERBEST']],
        };
        $failure = null;
        foreach ($paths as $path) {
            try {
                $response = $this->dispatchRead($path, ['ettn' => $uuid]);
                $document = $response->get('data');
                if (!is_array($document) || $document === [] || array_is_list($document)) {
                    throw new ApiException('GİB belge detayı beklenen biçimde dönmedi.', null, $response->get());
                }
                return $document;
            } catch (SessionExpiredException $e) {
                throw $e;
            } catch (ApiException $e) {
                $failure ??= $e;
            }
        }
        throw $failure;
    }

    /**
     * getLastDocument
     */
    public function getLastDocument(string $fromModifier = '-1 year', ?string $recipientId = null): array
    {
        $query = $this->onlyCurrent();

        if (!empty($recipientId)) {
            $query->findRecipientId($recipientId);
        }
        
        $lastDocument = $query->setLimit(1)
                              ->sortDesc()
                              ->getAll(
                                    curdate('d/m/Y', $fromModifier), 
                                    curdate('d/m/Y')
                                );
                             
        return $lastDocument 
            ? $this->getDocument($lastDocument[0]['ettn']) 
            : [];
    }

    /**
     * getHtml
     */
    public function getHtml(string $uuid, bool $signed = true): mixed
    {
        $response = $this->dispatchRead(['EARSIV_PORTAL_FATURA_GOSTER', 'RG_TASLAKLAR'], [
            'ettn'       => $this->setUuid($uuid),
            'onayDurumu' => ($signed ? 'Onaylandı' : 'Onaylanmadı'),
        ]);
        return $response->get('data');
    }
    
    /**
     * getDownloadURL
     */
    public function getDownloadURL(string $uuid, bool $signed = true): string
    {
        return $this->getGateway('download') . '?' . http_build_query([
            'token'      => $this->token,
            'ettn'       => $this->setUuid($uuid),
            'onayDurumu' => ($signed ? 'Onaylandı' : 'Onaylanmadı'),
            'belgeTip'   => $this->documentType->value,
            'cmd'        => 'EARSIV_PORTAL_BELGE_INDIR'
        ]);
    }

    /**
     * saveToDisk
     *
     * @param  string         $uuid
     * @param  string|null    $dirName
     * @param  string|null    $fileName
     * @return string|boolean
     */
    public function saveToDisk(string $uuid, ?string $dirName = null, ?string $fileName = null): string|bool
    {
        $saveDir = realpath($dirName ?? '.' . DIRECTORY_SEPARATOR);
        $fullDir = join(DIRECTORY_SEPARATOR, [$saveDir, $fileName ?? $uuid]) . '.zip';
        $options = [
            'http' => [
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36'
            ]
        ];

        if (!$saveDir) {
            throw new InvalidArgumentException("Geçersiz dosya yolu: {$dirName}");
        }
        if (file_put_contents($fullDir, file_get_contents($this->getDownloadURL($uuid), false, stream_context_create($options)))) {
            return $fullDir;
        }
        return false;
    }

    /**
     * cancellationRequest
     */
    public function cancellationRequest(string $uuid, string $explanation): string
    {
        $response = $this->request($this->getGateway('dispatch'),
            $this->setParams(['EARSIV_PORTAL_IPTAL_TALEBI_OLUSTUR', 'RG_TASLAKLAR'], [
                'ettn'          => $this->setUuid($uuid), 
                'onayDurumu'    => 'Onaylandı',
                'belgeTuru'     => $this->documentType->value,
                'talepAciklama' => $explanation,
            ])
        );
        return $response->get('data');
    }

    /**
     * objectionRequest
     */
    public function objectionRequest(string $uuid, ObjectionMethod $objectionMethod, string $documentId, string $documentDate, string $explanation): string
    {
        $response = $this->request($this->getGateway('dispatch'),
            $this->setParams(['EARSIV_PORTAL_ITIRAZ_TALEBI_OLUSTUR', 'RG_TASLAKLAR'], [
                'ettn'                => $this->setUuid($uuid), 
                'onayDurumu'          => 'Onaylandı',
                'belgeTuru'           => $this->documentType->value,
                'itirazYontemi'       => $objectionMethod->value,
                'referansBelgeId'     => $documentId,
                'referansBelgeTarihi' => $documentDate,
                'talepAciklama'       => $explanation,
            ])
        );
        return $response->get('data');
    }

    /**
     * getRequests
     */
    public function getRequests(string $startDate, string $endDate): array
    {
        return $this->listByDateRange(
            ['EARSIV_PORTAL_GELEN_IPTAL_ITIRAZ_TALEPLERINI_GETIR', 'RG_IPTALITIRAZTASLAKLAR'],
            $startDate, $endDate
        );
    }

    /**
     * getAll
     */
    public function getAll(string $startDate, string $endDate): array
    {
        return $this->filterDocuments($this->listDocumentSummaries($startDate, $endDate));
    }

    protected function listDocumentSummaries(string $startDate, string $endDate): array
    {
        return $this->listByDateRange(
            ['EARSIV_PORTAL_TASLAKLARI_GETIR', 'RG_TASLAKLAR'], $startDate, $endDate,
            ['hangiTip' => $this->testMode ? Type::eArsivDiger : Type::eArsivFatura], true
        );
    }

    /**
     * getAllIssuedToMe
     */
    public function getAllIssuedToMe(string $startDate, string $endDate, string $hourlySearch = 'NONE'): array
    {
        return $this->filterDocuments($this->listByDateRange(
            ['EARSIV_PORTAL_ADIMA_KESILEN_BELGELERI_GETIR', 'RG_ALICI_TASLAKLAR'], $startDate, $endDate,
            ['hourlySearchInterval' => $hourlySearch]
        ));
    }

    protected function listByDateRange(array $command, string $startDate, string $endDate, array $payload = [], bool $uniqueByUuid = false): array
    {
        $start = $this->parseDocumentDate($startDate);
        $end = $this->parseDocumentDate($endDate);
        if ($start > $end) {
            throw new InvalidFormatException('Başlangıç tarihi bitiş tarihinden sonra olamaz.');
        }

        $documents = [];
        while ($start <= $end) {
            // GİB iki tarih arasında en fazla 7 gün kabul ediyor; iki uç da dahil.
            $windowEnd = min($start->modify('+7 days'), $end);
            $response = $this->dispatchRead($command, [
                'baslangic' => $start->format('d/m/Y'),
                'bitis' => $windowEnd->format('d/m/Y'),
            ] + $payload);
            $rows = $response->get('data');
            if (!is_array($rows) || !array_is_list($rows)) {
                throw new ApiException('GİB belge listesi beklenen biçimde dönmedi.', null, $response->get());
            }
            $seen = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    throw new ApiException('GİB belge listesi geçersiz kayıt içeriyor.', null, $response->get());
                }
                if ($uniqueByUuid) {
                    $uuid = $row['ettn'] ?? null;
                    if (!is_string($uuid) || !Uuid::isValid($uuid) || isset($seen[strtolower($uuid)])) {
                        throw new ApiException('GİB belge listesinde geçersiz veya tekrarlanan ETTN var.', null, $response->get());
                    }
                    $seen[strtolower($uuid)] = true;
                    // Ayrı aralıklarda tekrar görünen belge son haliyle bir kez döner.
                    $documents[strtolower($uuid)] = $row;
                } else {
                    $documents[] = $row;
                }
            }
            $start = $windowEnd->modify('+1 day');
        }
        return array_values($documents);
    }

    protected function parseDocumentDate(string $date): \DateTimeImmutable
    {
        if (preg_match('/\A(\d{2})\/(\d{2})\/(\d{4})\z/', $date, $parts) !== 1
            || !checkdate((int) $parts[2], (int) $parts[1], (int) $parts[3])) {
            throw new InvalidFormatException('Tarih geçerli formatta değil.');
        }
        return \DateTimeImmutable::createFromFormat('!d/m/Y', $date, new \DateTimeZone('Europe/Istanbul'));
    }

    /**
     * filterDocuments
     */
    protected function filterDocuments(?array $documents): array
    {
        if (is_null($documents)) return [];
        
        if (sizeof($this->filters)) {
            array_map(function ($key, $val) use (&$documents){
                $documents = array_filter($documents, function ($document) use ($key, $val) {
                    return isset($document[$key]) && (
                        $document[$key] === $val || str_contains(strtolower($document[$key]), strtolower($val))
                    );
                });
            }, array_keys($this->filters), $this->filters);
        }
        
        $this->setRowCount(sizeof($documents));
        $this->setFilters();

        if ($this->sortByDesc) {
            $documents = array_reverse($documents);
        }
        if (sizeof($this->limit)) {
            $documents = array_slice($documents, ...$this->limit); $this->setLimit();
        }
        return $this->mapColumn($documents);
    }

    /**
     * selectColumn
     */
    public function selectColumn(string $column, $key = null): self
    {
        $this->column = [$column, $key];
        return $this;
    }

    /**
     * mapColumn
     */
    public function mapColumn(array $data): array
    {
        if (sizeof($this->column)) {
            $data = array_column($data, ...$this->column);
            $this->column = [];
        }
        return $data;
    }

    /**
     * setFilters
     */
    protected function setFilters(array $filter = []): void
    {
        $this->filters = $filter ? array_merge($this->filters, $filter) : $filter;
    }

    /**
     * setLimit
     */
    public function setLimit(int $limit = 0, int $offset = 0): self
    {
        $this->limit = !$limit ? [] : [$offset, $limit];
        return $this;
    }

    /**
     * sortAsc
     */
    public function sortAsc(): self
    {
        $this->sortByDesc = false;
        return $this;
    }

    /**
     * sortDesc
     */
    public function sortDesc(): self
    {
        $this->sortByDesc = true;
        return $this;
    }

    /**
     * setRowCount
     */
    protected function setRowCount(int $rowCount = 0): void
    {
        $this->rowCount = $rowCount;
    }

    /**
     * rowCount
     */
    public function rowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * setLastId
     */
    protected function setLastId(string $uuid): void
    {
        $this->lastId = $uuid;
    }

    /**
     * lastId
     */
    public function lastId(): string
    {
        return $this->lastId;
    }

    /**
     * onlySigned
     */
    public function onlySigned(): self
    {
        $this->setFilters(['onayDurumu' => 'Onaylandı']); 
        return $this;
    }

    /**
     * onlyUnSigned
     */
    public function onlyUnsigned(): self
    {
        $this->setFilters(['onayDurumu' => 'Onaylanmadı']); 
        return $this;
    }

    /**
     * onlyDeleted
     */
    public function onlyDeleted(): self
    {
        $this->setFilters(['onayDurumu' => 'Silinmiş']); 
        return $this;
    }

    /**
     * onlyCurrent
     */
    public function onlyCurrent(): self
    {
        $this->setFilters(['belgeTuru' => $this->documentType->value]); 
        return $this;
    }

    /**
     * onlyInvoice
     */
    public function onlyInvoice(): self
    {
        $this->setFilters(['belgeTuru' => DocumentType::Invoice->value]); 
        return $this;
    }

    /**
     * onlyProducerReceipt
     */
    public function onlyProducerReceipt(): self
    {
        $this->setFilters(['belgeTuru' => DocumentType::ProducerReceipt->value]);
        return $this;
    }

    /**
     * onlySelfEmployedReceipt
     */
    public function onlySelfEmployedReceipt(): self
    {
        $this->setFilters(['belgeTuru' => DocumentType::SelfEmployedReceipt->value]);
        return $this;
    }

    /**
     * findRecipientName
     */
    public function findRecipientName(string $value): self
    {
        $this->setFilters(['aliciUnvanAdSoyad' => $value]);
        return $this;
    }

    /**
     * findRecipientId
     */
    public function findRecipientId(string $value): self
    {
        $this->setFilters(['aliciVknTckn' => $value]);
        return $this;
    }

    /**
     * findDocumentId
     */
    public function findDocumentId(string $value): self
    {
        $this->setFilters(['belgeNumarasi' => $value]);
        return $this;
    }

    /**
     * findEttn
     */
    public function findEttn(string $value): self
    {
        $this->setFilters(['ettn' => $value]);
        return $this;
    }

    /**
     * setParams
     */
    public function setParams(array $command, array $payload = []): array
    {
        list($cmd, $pageName) = $command;

        return [
            'callid'   => Uuid::uuid1()->toString(),
            'token'    => $this->token,
            'cmd'      => $cmd,
            'pageName' => $pageName,
            'jp'       => json_encode($payload ?: (object) $payload),
        ];
    }
        
    protected function dispatchRead(array $command, array $payload = []): Client
    {
        try {
            return $this->request($this->getGateway('dispatch'), $this->setParams($command, $payload));
        } catch (SessionExpiredException $e) {
            if (!$this->username || !$this->password) throw $e;
            $this->login();
        }
        // Yalnızca okuma tekrarlanır; parametreler yeni token ile kurulur.
        return $this->request($this->getGateway('dispatch'), $this->setParams($command, $payload));
    }

    protected function request(string $url, ?array $parameters = null, bool $post = true, array $options = []): Client
    {
        if ($url === $this->getGateway('dispatch') && $this->token && !$this->portalReady) {
            $this->bootPortalSession();
        }
        $base = $this->testMode ? self::API['gateways']['test'] : self::API['gateways']['prod'];
        $options['cookies'] = $this->cookieJar ??= new CookieJar();
        $options['headers'] = array_replace([
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin' => $base,
            'Referer' => $base . ($this->portalReady ? '/index.jsp' : '/intragiris.html'),
        ], $options['headers'] ?? []);
        return new Client($url, $parameters, $post, $options);
    }

    protected function bootPortalSession(): void
    {
        $base = $this->testMode ? self::API['gateways']['test'] : self::API['gateways']['prod'];
        $this->request($base . '/index.jsp', ['token' => $this->token], false, ['expect_json' => false]);
        $this->portalReady = true;
    }

    /**
     * getGateway
     */
    public function getGateway(string $path): string
    {
        if(!array_key_exists($path, self::API['paths'])) {
            throw new InvalidArgumentException('Geçersiz path gönderildi.');
        }
        return ($this->testMode 
            ? self::API['gateways']['test']
            : self::API['gateways']['prod']) . self::API['paths'][$path];   
    }
}
