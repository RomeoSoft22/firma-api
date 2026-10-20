<?php

// Mensajes de validación en español para las reglas que usa esta API.
return [
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'in' => 'El valor de :attribute no es válido.',
    'regex' => 'El campo :attribute contiene caracteres no permitidos.',
    'url' => 'El campo :attribute debe ser una URL válida (http o https).',
    'date' => 'El campo :attribute no es una fecha válida.',
    'dimensions' => 'La imagen excede las dimensiones permitidas (máx. 2000×2000 px).',
    'exists' => 'El :attribute seleccionado no existe.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'file' => 'El :attribute no debe superar los :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe superar los :max caracteres.',
    ],
    'mimes' => 'El :attribute debe ser de tipo: :values.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',

    'attributes' => [
        'archivo' => 'archivo PDF',
        'imagen' => 'imagen',
        'descripcion' => 'descripción',
        'firmante' => 'firmante',
        'fecha_firma' => 'fecha de firma',
        'lote_id' => 'lote',
        'mensaje' => 'mensaje',
        'nombre' => 'nombre',
        'total' => 'total',
        'tsa_url' => 'URL del TSA',
        'ubicacion' => 'lugar de firma',
        'motivo_defecto' => 'motivo por defecto',
        'sufijo_firmado' => 'sufijo de archivos firmados',
        'firma_ancho' => 'ancho del sello',
        'firma_alto' => 'alto del sello',
        'ancho' => 'ancho del sello',
        'alto' => 'alto del sello',
    ],
];
