<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key and Organization
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
    */

    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Project
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API project. This is used optionally in
    | situations where you are using a legacy user API key and need association
    | with a project. This is not required for the newer API keys.
    */
    'project' => env('OPENAI_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Base URL
    |--------------------------------------------------------------------------
    |
    | Here you may specify your OpenAI API base URL used to make requests. This
    | is needed if using a custom API endpoint. Defaults to: api.openai.com/v1
    */
    'base_uri' => env('OPENAI_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout may be used to specify the maximum number of seconds to wait
    | for a response. GPT-5 / o1 / o3 reasoning models can easily take 60-180s
    | to respond because they spend tokens on internal reasoning before producing
    | any output, so the SDK default of 30s is too low for this project.
    */

    'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Web Search (enriquecimiento con datos de internet)
    |--------------------------------------------------------------------------
    |
    | Modelo y tipo de herramienta usados por WebResearchService cuando un tipo
    | de reporte tiene "usar internet" activo. La búsqueda corre por la Responses
    | API de OpenAI con la herramienta web_search nativa. Cambiá el tool a
    | 'web_search_preview' si tu modelo/versión de la API lo requiere.
    */

    'search_model' => env('OPENAI_SEARCH_MODEL', 'gpt-4o-mini'),
    'web_search_tool' => env('OPENAI_WEB_SEARCH_TOOL', 'web_search'),
];
