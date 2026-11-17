<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_a_raiz_redireciona_para_a_pagina_de_agendamento(): void
    {
        $this->get('/')->assertRedirect('/agendar');
    }
}
