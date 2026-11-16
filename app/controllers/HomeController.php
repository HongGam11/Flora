<?php

final class HomeController
{
    public function index(): void
    {
        (new ProductController())->home();
    }
}