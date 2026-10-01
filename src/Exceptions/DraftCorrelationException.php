<?php

declare(strict_types=1);

namespace Mlevent\Fatura\Exceptions;

/** Oluşturma gönderildi; sonucu uzlaştırmadan aynı belge tekrar gönderilmemeli. */
class DraftCorrelationException extends ApiException {}
