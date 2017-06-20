<?php

namespace App\Http\Requests\Solarium;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;


class QueryRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize ()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules ()
    {
        return [
            'leftLl'  => 'required|string' ,
            'rightLl' => 'required|string' ,
            'd'       => 'required|numeric' ,
            'pt'      => 'required|string' ,
        ];
    }

    public function formatErrors ( Validator $validator )
    {
        return response_fail( -1 , $validator->errors()->first() );
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  string $errors
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response ( array $errors )
    {
        return new JsonResponse( $errors );
//        return $this->redirector->to( $this->getRedirectUrl() )->withInput( $this->except( $this->dontFlash ) )->withErrors( $errors ,
//            $this->errorBag );
    }
}
