<?php

declare(strict_types=1);

namespace alexeevdv\SumSub\Request;

final class CreateApplicantDataRequest
{

    public function __construct(
        private readonly string            $levelName,
        private readonly string            $externalUserId,
        private readonly string|null       $sourceKey = null,
        private readonly string|null       $email = null,
        private readonly string|null       $phone = null,
        private readonly string|null       $lang = null,
        private readonly array|null        $metadata = null,
        private readonly object|array|null $fixedInfo = null,
        private readonly object|array|null $info = null,
        private readonly string|null       $type = null,
    )
    {
    }

    public function getLevelName(): string|null
    {
        return $this->levelName;
    }

    public function getExternalUserId(): string|null
    {
        return $this->externalUserId;
    }

    public function getSourceKey(): string|null
    {
        return $this->sourceKey;
    }

    public function getfixedInfo(): array|null
    {
        return $this->fixedInfo;
    }
}
