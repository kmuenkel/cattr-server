<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;
use Symfony\Component\HttpFoundation\Response;

class IntervalAlreadyDeletedException extends HttpException
{
    protected $errorCode = 'interval_already_deleted';
    protected $status = Response::HTTP_CONFLICT;
}
