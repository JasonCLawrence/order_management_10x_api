<?php
namespace App\Http;

class ApiResponse
{

//this class provides a simple way to return errors

	public function withError ($message, $statusCode=500)
	{
	    return response()->json([
	        'error' => [
	            'message' => $message,
	            'status_code' => $statusCode
	        ]
	    ], $statusCode);

	}

	public function withSuccess ($message, $statusCode=200)
	{
	    return response()->json([
	        'success' => [
	            'message' => $message,
	            'status_code' => $statusCode,
	        ]
	    ], $statusCode);

	}

	public function withSuccessData ($data, $statusCode=200)
	{
	    return response()->json([
	        'success' => [
	            'status_code' => $statusCode,
	            'data'	=> $data,
	        ]
	    ], $statusCode);

	}

}

