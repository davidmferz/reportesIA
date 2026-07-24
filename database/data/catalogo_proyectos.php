<?php

/**
 * Catálogo de clasificación de proyectos.
 *
 * Extraído de docs/Generador_Proyectos_Anidado_Excel_2019.xlsx
 * (hoja "Clasificación" para la jerarquía, hoja "Listas_2019" para servicios y documentos).
 *
 * No editar a mano: regenerar desde el Excel si el catálogo cambia.
 */
return [
    'hierarchy' => [
        'Primario' => [
            'Recursos naturales' => [
                'Agricultura' => [
                    'Servicios para Horticultura',
                    'Servicios enfocados a Cultivo',
                    'Operación agrícola',
                ],
                'Ganadería' => [
                    'Manejo y explotación ganadera',
                ],
                'Pesca' => [
                    'Conservación y explotación pesquera y acuicultura',
                ],
                'Explotación forestal' => [
                    'Explotación de recursos forestales',
                    'Conservación y mantenimiento forestal',
                    'Conservación y administración hidráulica',
                ],
            ],
            'Minería' => [
                'Metálica y cantera' => [
                    'Extracción de metales y no metales',
                ],
                'Energéticos' => [
                    'Extracción de energéticos',
                    'Producción eneregética',
                    'Mentenimiento energético',
                    'Administración de información energética',
                    'Administración de proyectos energéticos',
                ],
            ],
        ],
        'Secundario' => [
            'Construcción' => [
                'Inmobiliario' => [
                    'Mantenimiento estructural y control de plagas',
                    'Construcción residencial',
                    'Construcción no residencial',
                ],
                'Obra civil' => [
                    'Obra civil pública',
                ],
                'Obra especializada' => [
                    'Obra y mantenimiento de servicios',
                ],
            ],
            'Industria' => [
                'Industrias químicas del plástico y hule' => [
                    'Servicios de producción petroquímica, farmacéutica y biotecnología',
                ],
                'Manufactura de madera y papel' => [
                    'Servicios de procesamiento de madera, celulosa y papel',
                ],
                'Industrias metálicas' => [
                    'Servicios de fundición, y terminación de metales',
                ],
                'Industria alimentaria' => [
                    'Productos alimenticios, bebidas y tabaco',
                ],
                'Industrias textiles, prendas de vestir e industrias del cuero' => [
                    'Servicios a textileras y maquiladoras para producción',
                ],
                'Apoyo industrial' => [
                    'Servicios de empaque, acomodo y mantenimiento',
                ],
                'Maquinaria y equipo' => [
                    'Servicios para producción de maquinaria, transporte y equipos',
                ],
                'Industria de la transformación' => [
                    'Servicios para maquinado, corte y procesado de materiales metálicos',
                ],
                'Manejo y tratamiento de residuos' => [
                    'Manejo y tratamiento de residuos peligrosos y no peligrosos',
                    'Tratamiento, eliminación y desintoxicación nueclear y tóxica',
                ],
            ],
        ],
        'Terciario' => [
            'Mediomabiente y ecología' => [
                'Administración medioambiental' => [
                    'Servicios de evaluación, planeción, auditoría y asesoría ambiental',
                ],
                'Protección y seguridad ambiental' => [
                    'Servicios de seguridad y reahbilitación ambiental',
                    'Servicios de control de contaminación de aire, suelo y agua',
                    'Servicios de control de agentes contaminantes',
                ],
            ],
            'Transporte y almacen' => [
                'Transporte y comunicación' => [
                    'Traslado de mercancías',
                    'Traslado de personas',
                    'Manejo y empaque de materiales y mercancías',
                    'Almacenaje',
                    'Servicios de navegación, inspección, porturarios y de contenedores',
                    'Mantenimiento de transportes',
                ],
            ],
            'Servicios empresariales' => [
                'Gestión y administración de empresas' => [
                    'Servicios de consultoria de corporativa industrial y de proyectos',
                    'Servicios de recursos y capital humano',
                    'Servicios de consultoría comercial',
                    'Servicios de apoyo a la administración',
                ],
                'Asesoría jurídica,económica y de inversiones' => [
                    'Asesoría jurídica',
                    'Asesoría inmobiliaria',
                ],
                'Publicidad y mercadotécnica' => [
                    'Servicios de mercadotecnia y promoción',
                ],
            ],
            'Servicios de ingeniería' => [
                'Servicios de ingeniería de manufactura' => [
                    'Servicios ingenieriles enfocados a industria, construcción y transformación',
                    'Servicios enfocados al desarrollo industrial',
                ],
                'Servicios de investigación económica' => [
                    'Servicios de análisis económico',
                    'Servicios de recopilación, recuento y análisis estadístico',
                ],
                'Servicios de información estadísitica' => [
                    'Recopilación y manejo de información estadística',
                ],
                'Servicios de tecnolgías de información' => [
                    'Internet e informática',
                ],
            ],
            'Servicios de publicidad, diseño gráfico e interpretación' => [
                'Servicios de diseño publicitario' => [
                    'Diseño de publicidad',
                    'Impresiones y fotocopiado',
                    'Fotografía y cine',
                    'Diseño gráfico',
                    'Servicios artísticos y de espectáculos',
                ],
            ],
            'Administración pública' => [
                'Servicios a la comunidad' => [
                    'Servicios públicos básicos',
                    'Comunicaciones y telecomunicaciones',
                    'Acervos de información física y digital',
                ],
                'Política y administración pública' => [
                    'Intitutos políticos',
                    'Política y sociedad',
                    'Relaciones diplomáticas',
                    'Administración y finanzas públicas',
                    'Servicios tributarios',
                    'Política comercial',
                ],
                'Servicios de cooperatividad' => [
                    'Apoyo humanitario',
                    'Apoyos sociales y comunitarios',
                ],
            ],
            'Actividades financieras' => [
                'Servicios financieros' => [
                    'Finanzas y capitalización',
                    'Servicios contables',
                    'Banca y servicios financieros',
                    'Aseguradoras e inversiones para retiro',
                    'Servicios crediticios',
                ],
            ],
            'Servicios de salud' => [
                'Salubridad' => [
                    'Operación y administración de unidades hospitalarias',
                    'Prevención y control de enfermedades',
                    'Médicos y servicios de asistencia',
                ],
                'Investigación médica' => [
                    'Investigación médica y experimentación',
                ],
                'Servicios alternativos' => [
                    'Medicina alternativa',
                ],
                'Servicios complementarios' => [
                    'Nutrición',
                    'Equipo médico y conservación',
                    'Servicios funerarios',
                ],
            ],
            'Servicios educativos' => [
                'Servicios educativos profesionales y técnicos' => [
                    'Educación profesional',
                    'Educación no escolarizada',
                ],
                'Sistemas educativos' => [
                    'Servicios a instituciones educativas',
                    'Educación artística, militar, y especilizada',
                    'Instalaciones educativas',
                ],
            ],
            'Servicios turísticos y de entretenimiento' => [
                'Servicios restauranteros' => [
                    'Restaurantes y servicios de alimentos',
                ],
                'Alojamiento de personas' => [
                    'Hoteles y servicios de alojamiento',
                ],
                'Agencias de viajes y turismo' => [
                    'Servicios de turismo y apoyo al turista',
                    'Entretenimiento artísitco',
                    'Entretenimiento deportivo',
                    'Entretenimiento general',
                ],
            ],
            'Servicios personales' => [
                'Apoyo personal y doméstico' => [
                    'Asistencia de mejora personal',
                    'Asistencia doméstica y de cuidado personal',
                ],
            ],
            'Servicios de asociación' => [
                'Apoyo y asociación laboral' => [
                    'Asociaciones profesionales, laborales y de negocios',
                    'Asociaciones religiosas',
                    'Organizaciones sociales',
                    'Organizaciones cívicas',
                ],
            ],
            'Seguridad, vigilancia y milicia' => [
                'Servicios de seguridad publica y defensa' => [
                    'Seguridad pública',
                    'Defensa nacional',
                    'Seguridad privada',
                ],
            ],
            'Servicios de apoyo a la construcción' => [
                'Terrenos y parcelas' => [
                    'Lotes para uso residencial, comercial y gubernamental',
                    'Vías de comunicación',
                ],
                'Equipos para edificios y prefabricados' => [
                    'Estructuras para edificios civiles, mercantiles, religiosos y militares',
                    'Inmuebles móviles y equipo',
                    'Estructuras prefabricados y ensamblados',
                ],
            ],
        ],
        'Cuaternario' => [
            'Servicios de ciencia, desarrollo y tecnología' => [
                'Informáticos' => [
                    'Desarrollo y mantenimiento de software',
                ],
            ],
        ],
    ],

    'services' => [
        'Almacenamiento y manejo de materiales' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Diagnóstico'],
        'Atención y experiencia del usuario' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Técnicas', 'Diagnóstico'],
        'Calidad de productos y servicios' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Diagnóstico', 'Verificación'],
        'Conservación y mantenimiento' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Diagnóstico', 'Verificación'],
        'Estandarización de procesos' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Procedimiento', 'Método', 'Técnicas', 'Diagnóstico'],
        'Gestión administrativa' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Técnicas', 'Diagnóstico'],
        'Gestión ambiental' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Diagnóstico', 'Verificación'],
        'Gestión comercial y posicionamiento' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Método', 'Técnicas', 'Acciones'],
        'Información y análisis estadístico' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Método', 'Técnicas', 'Diagnóstico', 'Informe'],
        'Logística y distribución' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Diagnóstico'],
        'Seguridad y control preventivo' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Diagnóstico', 'Verificación'],
        'Tecnología y sistemas de información' => ['Análisis', 'Modelo', 'Prueba piloto', 'Seguimiento', 'Monitoreo', 'Plan', 'Procedimiento', 'Método', 'Diagnóstico'],
    ],
];
