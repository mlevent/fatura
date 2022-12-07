<?php

declare(strict_types=1);

namespace Mlevent\Fatura;

use Mlevent\Fatura\Enums\DocumentType;
use Mlevent\Fatura\Enums\ObjectionMethod;
use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\InvalidArgumentException;
use Mlevent\Fatura\Exceptions\InvalidFormatException;
use Mlevent\Fatura\Interfaces\ModelInterface;
use Mlevent\Fatura\Models\UserDataModel;
use Mlevent\Fatura\Utils\FormatValidator;
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
     * @var string|array
     */
    protected string|array $uuid;

    /**
     * @var boolean
     */
    protected bool $sortByDesc = false;
    
    /**
     * @var integer
     */
    protected int $rowCount = 0;

    /**
     * @var array
     */
    protected array $column  = [],
                    $filters = [],
                    $limit   = [];

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
    public function setCredentials(string $username = null, string $password = null): self
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
    public function setTestCredentials(string $username = null, string $password = null): self
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
        $response = new Client($this->getGateway('esign'), [
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
    public function setToken(string $token = null): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * getToken
     */
    public function getToken(): string
    {
        return $this->token;
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
    public function login(string $username = null, string $password = null): self
    {
        if ($username && $password) {
            $this->setCredentials($username, $password);
        }

        $response = new Client($this->getGateway('login'), [
            'assoscmd' => $this->testMode ? 'login' : 'anologin',
            'userid'   => $this->username,
            'sifre'    => $this->password,
            'sifre2'   => $this->password,
            'parola'   => $this->password,
        ]);

        $this->setToken($response->get('token'));
        return $this;
    }
    
    /**
     * logout
     */
    public function logout(): bool
    {
        new Client($this->getGateway('login'), [
            'assoscmd' => 'logout',
            'token'    => $this->token,
        ]);

        $this->setCredentials();
        $this->setToken();
        return true;
    }
        
    /**
     * getRecipientData
     */
    public function getRecipientData(string $taxOrTrId): array
    {
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['SICIL_VEYA_MERNISTEN_BILGILERI_GETIR', 'RG_BASITFATURA'], [
                'vknTcknn' => $taxOrTrId
            ])
        );
        return $response->get('data');
    }
        
    /**
     * getUserData
     */
    public function getUserData(): array
    {
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_KULLANICI_BILGILERI_GETIR', 'RG_KULLANICI'])
        );
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

        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_KULLANICI_BILGILERI_KAYDET', 'RG_KULLANICI'], $userData)
        );
        return $response->get('data') ? true : false;
    }

    /**
     * Portalda kayıtlı GSM numarası
     */
    public function getPhoneNumber(): ?string
    {
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_TELEFONNO_SORGULA', 'RG_BASITTASLAKLAR'])
        );
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
        $response = new Client($this->getGateway('dispatch'), 
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
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['0lhozfib5410mp', 'RG_SMSONAY'], [
                'DATA'  => $this->setUuid($documents),
                'SIFRE' => $code, 
                'OID'   => $oid, 
                'OPR'   => 1, 
            ])
        );
        if ($response->object('data')->sonuc === '1') {
            $this->setRowCount(sizeof($documents));
            return true;
        }
        return false;
    }

    /**
     * createDraft
     */
    public function createDraft(ModelInterface|array $modelData): bool
    {
        $modelData = $modelData instanceof ModelInterface 
            ? $modelData->export() 
            : $modelData;

        $requestPath = match ($this->documentType) {
            DocumentType::Invoice             => ['EARSIV_PORTAL_FATURA_OLUSTUR', 'RG_BASITFATURA'],
            DocumentType::ProducerReceipt     => ['EARSIV_PORTAL_MUSTAHSIL_OLUSTUR', 'RG_MUSTAHSIL'],
            DocumentType::SelfEmployedReceipt => ['EARSIV_PORTAL_SERBEST_MESLEK_MAKBUZU_OLUSTUR', 'RG_SERBEST'],
        };

        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams($requestPath, $modelData)
        );

        if (!str_contains($response->object('data'), 'başarıyla')) {
            throw new ApiException($response->object('data'), $modelData, $response);
        }
        return true;
    }

    /**
     * deleteDraft
     */
    public function deleteDraft(array $documents, string $reason = 'Hatalı İşlem'): bool
    {
        $deletedDocuments = array_map(function ($uuid) {
            return [
                'belgeTuru' => $this->documentType->value,
                'ettn'      => $uuid,
            ];
        }, $this->setUuid($documents));

        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_FATURA_SIL', 'RG_TASLAKLAR'], [
                'silinecekler' => $deletedDocuments, 
                'aciklama'     => $reason,
            ])
        );
        if (preg_match('/(\d+)/', $response->get('data'), $affectedRow)) {
            $this->setRowCount((int) $affectedRow[1]); 
            return true;
        }
        return false;
    }

    /**
     * getDocument
     */
    public function getDocument(string $uuid): array
    {
        $requestPath = match ($this->documentType) {
            DocumentType::Invoice             => ['EARSIV_PORTAL_FATURA_GETIR', 'RG_TASLAKLAR'],
            DocumentType::ProducerReceipt     => ['EARSIV_PORTAL_MUSTAHSIL_GETIR', 'RG_MUSTAHSIL'],
            DocumentType::SelfEmployedReceipt => ['EARSIV_PORTAL_SERBEST_MESLEK_GETIR', 'RG_SERBEST'],
        };

        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams($requestPath, [
                'ettn' => $this->setUuid($uuid)
            ])
        );
        return $response->get('data');
    }

    /**
     * getLastDocument
     */
    public function getLastDocument(): array
    {
        $lastDocument = $this->onlyCurrent()
                             ->setLimit(1)
                             ->sortDesc()
                             ->getAll('01/01/2020', curdate('d/m/Y'));
                             
        return $lastDocument 
            ? $this->getDocument($lastDocument[0]['ettn']) 
            : [];
    }

    /**
     * getHtml
     */
    public function getHtml(string $uuid, bool $signed = true): mixed
    {
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_FATURA_GOSTER', 'RG_TASLAKLAR'], [
                'ettn'       => $this->setUuid($uuid), 
                'onayDurumu' => ($signed ? 'Onaylandı' : 'Onaylanmadı'),
            ])
        );
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
     * cancellationRequest
     */
    public function cancellationRequest(string $uuid, string $explanation): string
    {
        $response = new Client($this->getGateway('dispatch'), 
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
        $response = new Client($this->getGateway('dispatch'), 
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
        if (!FormatValidator::date($startDate) || !FormatValidator::date($endDate)) {
            throw new InvalidFormatException('Tarih geçerli formatta değil.');
        }
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_GELEN_IPTAL_ITIRAZ_TALEPLERINI_GETIR', 'RG_IPTALITIRAZTASLAKLAR'], [
                'baslangic' => $startDate, 
                'bitis'     => $endDate,
            ])
        );
        return $response->get('data');
    }

    /**
     * selectColumn
     */
    public function selectColumn(string|array $column): self
    {
        $this->column = (array) $column;
        return $this;
    }

    /**
     * getAll
     */
    public function getAll(string $startDate, string $endDate): array
    {
        if (!FormatValidator::date($startDate) || !FormatValidator::date($endDate)) {
            throw new InvalidFormatException('Tarih geçerli formatta değil.');
        }
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_TASLAKLARI_GETIR', 'RG_TASLAKLAR'], [
                'baslangic' => $startDate, 
                'bitis'     => $endDate, 
                'hangiTip'  => 'Buyuk',
            ])
        );
        return $this->filterDocuments($response->get('data'));
    }

    /**
     * getAllIssuedToMe
     */
    public function getAllIssuedToMe(string $startDate, string $endDate): array
    {
        if (!FormatValidator::date($startDate) || !FormatValidator::date($endDate)) {
            throw new InvalidFormatException('Tarih geçerli formatta değil.');
        }
        $response = new Client($this->getGateway('dispatch'), 
            $this->setParams(['EARSIV_PORTAL_ADIMA_KESILEN_BELGELERI_GETIR', 'RG_ALICI_TASLAKLAR'], [
                'baslangic' => $startDate, 
                'bitis'     => $endDate,
            ])
        );
        return $this->filterDocuments($response->get('data'));
    }

    /**
     * filterDocuments
     */
    protected function filterDocuments(array $documents): array
    {
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
     * mapColumn
     */
    public function mapColumn(array $data): array
    {
        if (sizeof($this->column)) {
            $data = array_map(function($item) {
                return array_intersect_key($item, array_flip($this->column));
            }, $data);
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