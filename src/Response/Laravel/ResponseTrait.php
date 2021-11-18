<?php


namespace Iayoo\ApiResponse\Response\Laravel;


use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    protected $httpStatusCode = 200;

    protected $statusCode = 0;

    protected $errorCode = 0;

    /**
     * @param int $httpStatusCode
     */
    public function setHttpStatusCode($httpStatusCode)
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @param int $errorCode
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function success($message = 'success', $data = [], $code = 0)
    {
        if (is_array($message)) {
            $data    = $message;
            $message = 'success';
        }
        return new JsonResponse(
            [
                'message' => $message,
                'status'  => $this->statusCode,
                'code'    => $code??$this->errorCode,
                'data'    => $data,
            ],
            $this->httpStatusCode,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public function error($error, $code = 40000, $trace = [])
    {
        return new JsonResponse(
            [
                'message' => $error,
                'code'    => $code??$this->errorCode,
                'status'  => $this->statusCode,
                'trace'   => $trace
            ],
            $this->httpStatusCode,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public function exception($request, $exception)
    {

        $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 40000;
        return new JsonResponse(
            [
                'message' => $exception->getMessage(),
                'status'  => $status,
                'data'    => $this->convertExceptionToArray($exception),
            ],
            $this->httpStatusCode,
            $this->isHttpException($e) ? $e->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    protected function response()
    {

    }
}