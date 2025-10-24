<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\RuleFactory as Rule;
use Respect\Validation\Validator as v;

class ProductController
{
    public function store(): string
    {
        try {
            $rules = [
                'name' => Rule::allOf(
                    Rule::required(),
                    Rule::string(),
                    Rule::minLength(3),
                    Rule::maxLength(200)
                ),
                'description' => Rule::optional(
                    Rule::string()->length(10, 1000)
                ),
                'price' => Rule::allOf(
                    Rule::required(),
                    Rule::numeric(),
                    Rule::minValue(0),
                    Rule::maxValue(999999.99)
                ),
                'stock' => Rule::allOf(
                    Rule::required(),
                    Rule::integer(),
                    Rule::minValue(0)
                ),
                'category' => Rule::allOf(
                    Rule::required(),
                    Rule::in(['electronics', 'clothing', 'books', 'home'])
                ),
                'tags' => Rule::optional(
                    Rule::array()->each(Rule::string()->length(1, 50))
                ),
                'metadata' => Rule::optional(
                    Rule::json()
                ),
                'images' => Rule::optional(
                    Rule::array()->each(
                        Rule::image(['image/jpeg', 'image/png', 'image/webp'])
                    )->max(5)
                )
            ];

            $data = Request::validate($rules);

            // Process the data...
            return Response::created(['id' => 1], 'Product created successfully');
        } catch (ValidationException $e) {
            return Response::error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return Response::error('Failed to create product', 500);
        }
    }
}
