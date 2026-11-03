@include('errors.layout', ['status' => $exception?->getStatusCode() ?? 400])
