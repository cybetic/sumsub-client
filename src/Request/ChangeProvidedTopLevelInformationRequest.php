<?php

declare(strict_types=1);

namespace alexeevdv\SumSub\Request;

final class ChangeProvidedTopLevelInformationRequest
{

    public function __construct(
        private readonly string      $applicantId,
        private readonly string|null $externalUserId = null,
        private readonly string|null $email = null,
        private readonly string|null $phone = null,
        private readonly bool|null   $deleted = null,
    )
    {
    }

    public function getApplicantId(): string|null
    {
        return $this->applicantId;
    }

    public function getExternalUserId(): string|null
    {
        return $this->externalUserId;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function getDeleted(): string|null
    {
        return $this->deleted;
    }

    public function getPhone(): string|null
    {
        return $this->phone;
    }
}
