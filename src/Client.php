<?php

declare(strict_types=1);

namespace alexeevdv\SumSub;

use alexeevdv\SumSub\Exception\BadResponseException;
use alexeevdv\SumSub\Exception\ConflictException;
use alexeevdv\SumSub\Exception\TransportException;
use alexeevdv\SumSub\Request\AccessTokenRequest;
use alexeevdv\SumSub\Request\ApplicantDataRequest;
use alexeevdv\SumSub\Request\ApplicantStatusRequest;
use alexeevdv\SumSub\Request\ChangeProvidedInfoRequest;
use alexeevdv\SumSub\Request\ChangeProvidedTopLevelInformationRequest;
use alexeevdv\SumSub\Request\CreateApplicantDataRequest;
use alexeevdv\SumSub\Request\DocumentImageRequest;
use alexeevdv\SumSub\Request\InspectionChecksRequest;
use alexeevdv\SumSub\Request\RequestApplicantChecksRequest;
use alexeevdv\SumSub\Request\RequestSignerInterface;
use alexeevdv\SumSub\Request\ResetApplicantRequest;
use alexeevdv\SumSub\Response\AccessTokenResponse;
use alexeevdv\SumSub\Response\ApplicantDataResponse;
use alexeevdv\SumSub\Response\ApplicantStatusResponse;
use alexeevdv\SumSub\Response\DocumentImageResponse;
use alexeevdv\SumSub\Response\InspectionChecksResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class Client implements ClientInterface
{
    public const PRODUCTION_BASE_URI = 'https://api.sumsub.com';

    public const STAGING_BASE_URI = 'https://test-api.sumsub.com';

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var RequestSignerInterface
     */
    private $requestSigner;

    /**
     * @var string
     */
    private $baseUrl;

    public function __construct(
        HttpClientInterface     $httpClient,
        RequestFactoryInterface $requestFactory,
        RequestSignerInterface  $requestSigner,
        string                  $baseUrl = self::PRODUCTION_BASE_URI
    )
    {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->requestSigner = $requestSigner;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @throws BadResponseException
     * @throws TransportException
     */
    public function getAccessToken(AccessTokenRequest $request): AccessTokenResponse
    {
        $queryParams = [
            'userId' => $request->getUserId(),
            'levelName' => $request->getLevelName(),
        ];

        if ($request->getTtlInSecs() !== null) {
            $queryParams['ttlInSecs'] = $request->getTtlInSecs();
        }

        $url = sprintf('%s/resources/accessTokens?%s', $this->baseUrl, http_build_query($queryParams));

        $httpRequest = $this->createApiRequest('POST', $url);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        $decodedResponse = $this->decodeResponse($httpResponse);

        return new AccessTokenResponse($decodedResponse['token'], $decodedResponse['userId']);
    }

    public function createApplicantData(CreateApplicantDataRequest $request): ApplicantDataResponse
    {
        $url = $this->baseUrl . '/resources/applicants?' . http_build_query(['levelName' => $request->getLevelName()]);

        $body = [
            'externalUserId' => $request->getExternalUserId(),
            'fixedInfo' => $request->getfixedInfo(),
        ];

        $httpRequest = $this->createApiRequest('POST', $url, $body);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() === 409) {
            throw new ConflictException($httpResponse);
        }

        if ($httpResponse->getStatusCode() !== 201) {
            throw new BadResponseException($httpResponse);
        }

        return new ApplicantDataResponse($this->decodeResponse($httpResponse));
    }

    public function changeApplicantInfo(ChangeProvidedInfoRequest $request): ApplicantDataResponse
    {
        $url = $this->baseUrl . '/resources/applicants/' . $request->getApplicantId() . '/fixedInfo';

        $httpRequest = $this->createApiRequest('PATCH', $url, $request->getfixedInfo());
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        return new ApplicantDataResponse($this->decodeResponse($httpResponse));
    }


    public function changeApplicantTopLevelInformation(ChangeProvidedTopLevelInformationRequest $request): ApplicantDataResponse
    {
        $url = $this->baseUrl . '/resources/applicants';

        $body = ['id' => $request->getApplicantId()];
        if ($request->getEmail() !== null) {
            $body['email'] = $request->getEmail();
        }

        if ($request->getExternalUserId() !== null) {
            $body['externalUserId'] = $request->getExternalUserId();
        }

        if ($request->getPhone() !== null) {
            $body['phone'] = $request->getPhone();
        }

        if ($request->getDeleted() !== null) {
            $body['deleted'] = $request->getDeleted();
        }

        $httpRequest = $this->createApiRequest('PATCH', $url, $body);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        return new ApplicantDataResponse($this->decodeResponse($httpResponse));
    }

    public function requestApplicantCheck(RequestApplicantChecksRequest $request): ApplicantDataResponse
    {
        $url = $this->baseUrl . '/resources/applicants/' . $request->getApplicantId() . '/status/pending';

        $httpRequest = $this->createApiRequest('POST', $url);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        return new ApplicantDataResponse($this->decodeResponse($httpResponse));
    }

    /**
     * @throws BadResponseException
     * @throws TransportException
     */
    public function getApplicantData(ApplicantDataRequest $request): ApplicantDataResponse
    {
        if ($request->getApplicantId() !== null) {
            $url = $this->baseUrl . '/resources/applicants/' . $request->getApplicantId() . '/one';
        } else {
            $url = $this->baseUrl . '/resources/applicants/-;externalUserId=' . $request->getExternalUserId() . '/one';
        }

        $httpRequest = $this->createApiRequest('GET', $url);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        return new ApplicantDataResponse($this->decodeResponse($httpResponse));
    }

    /**
     * @throws BadResponseException
     * @throws TransportException
     */
    public function resetApplicant(ResetApplicantRequest $request): void
    {
        $url = $this->baseUrl . '/resources/applicants/' . $request->getApplicantId() . '/reset';

        $httpRequest = $this->createApiRequest('POST', $url);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        $decodedResponse = $this->decodeResponse($httpResponse);
        $isOk = ($decodedResponse['ok'] ?? 0) === 1;

        if (!$isOk) {
            throw new BadResponseException($httpResponse);
        }
    }

    /**
     * @throws BadResponseException
     * @throws TransportException
     */
    public function getApplicantStatus(ApplicantStatusRequest $request): ApplicantStatusResponse
    {
        $url = $this->baseUrl . '/resources/applicants/' . $request->getApplicantId() . '/requiredIdDocsStatus';

        $httpRequest = $this->createApiRequest('GET', $url);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        return new ApplicantStatusResponse($this->decodeResponse($httpResponse));
    }

    /**
     * @throws BadResponseException
     * @throws TransportException
     */
    public function getDocumentImage(DocumentImageRequest $request): DocumentImageResponse
    {
        $url = $this->baseUrl . '/resources/inspections/' . $request->getInspectionId() . '/resources/' . $request->getImageId();

        $httpRequest = $this->createApiRequest('GET', $url);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        return new DocumentImageResponse($httpResponse);
    }

    public function getInspectionChecks(InspectionChecksRequest $request): InspectionChecksResponse
    {
        $url = $this->baseUrl . '/resources/inspections/' . $request->getInspectionId() . '/checks';

        $httpRequest = $this->createApiRequest('GET', $url);
        $httpResponse = $this->sendApiRequest($httpRequest);

        if ($httpResponse->getStatusCode() !== 200) {
            throw new BadResponseException($httpResponse);
        }

        return new InspectionChecksResponse($this->decodeResponse($httpResponse));
    }

    private function createApiRequest(string $method, string $uri, array|null $body = null): RequestInterface
    {
        $httpRequest = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');

        if ($body !== '' && $body !== null) {
            if (is_string($body) === false) {
                $body = json_encode($body);
            }
            $httpRequest = $httpRequest->withBody(Utils::streamFor($body));
        }

        return $this->requestSigner->sign($httpRequest);
    }

    /**
     * @throws TransportException
     */
    private function sendApiRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException($e);
        }
    }

    /**
     * @throws BadResponseException
     */
    private function decodeResponse(ResponseInterface $response): array
    {
        try {
            $result = json_decode($response->getBody()->getContents(), true);
            if ($result === null) {
                throw new \Exception(json_last_error_msg());
            }
            return $result;
        } catch (\Throwable $e) {
            throw new BadResponseException($response, $e);
        }
    }
}
