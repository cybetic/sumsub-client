<?php

declare(strict_types=1);

namespace alexeevdv\SumSub\Request;

final class ChangeProvidedInfoRequest
{

    public function __construct(
        private readonly string            $applicantId,
        private readonly object|array|null $fixedInfo = null,
    )
    {
    }

    public function getApplicantId(): string|null
    {
        return $this->applicantId;
    }

    public function getfixedInfo(): array|null
    {
        return $this->fixedInfo;
    }
}
