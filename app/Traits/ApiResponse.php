<?php

namespace App\Traits;

trait ApiResponse
{
    private function response(bool $success, int $code, string $message, $data = null)
    {
        return response()->json([
            'success' => $success,
            'code'    => $code,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    protected function retrieved($data = null, string $message = null)
    {
        $message = $message ?? "{$this->resourceName} retrieved successfully";
        return $this->response(true, 200, $message, $data);
    }

    protected function created($data = null, string $message = null)
    {
        $message = $message ?? "{$this->resourceName} created successfully";
        return $this->response(true, 201, $message, $data);
    }

    protected function updated($data = null)
    {
        return $this->response(true, 200, "{$this->resourceName} updated successfully", $data);
    }

    protected function deleted()
    {
        return $this->response(true, 200, "{$this->resourceName} deleted successfully");
    }

    protected function loggedOut()
    {
        return $this->response(true, 200, 'Logged out successfully');
    }

    // 4xx Client Error
    protected function badRequest(string $message = 'Bad Request', $data = null)
    {
        return $this->response(false, 400, $message, $data);
    }

    protected function unauthorized(string $message = 'Unauthenticated')
    {
        return $this->response(false, 401, $message);
    }

    protected function forbidden(string $message = 'Forbidden')
    {
        return $this->response(false, 403, $message);
    }

    protected function notFound(string $message = 'Not Found')
    {
        return $this->response(false, 404, $message);
    }

    protected function validationError($errors, string $message = 'Validation Error')
    {
        return $this->response(false, 422, $message, $errors);
    }

    // 5xx Server Error
    protected function serverError(string $message = 'Internal Server Error')
    {
        return $this->response(false, 500, $message);
    }
}