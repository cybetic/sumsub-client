<?php

declare(strict_types=1);

namespace alexeevdv\SumSub\Request;

final class RequestApplicantChecksRequest
{

    public function __construct(private readonly string $applicantId)
    {
    }

    public function getApplicantId(): string
    {
        return $this->applicantId;
    }
}
