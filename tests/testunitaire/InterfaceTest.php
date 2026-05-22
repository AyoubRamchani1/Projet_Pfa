<?php

use PHPUnit\Framework\TestCase;

class InterfaceTest extends TestCase
{
    private string $homeHtml;
    private string $descriptionHtml;

    protected function setUp(): void
    {
        // ✅ Chemins corrigés : __DIR__ remonte de tests/ → racine → pages
        $homePath        = __DIR__ . '/../../home page/home.php';
        $descriptionPath = __DIR__ . '/../../description page/description.php';

        $this->homeHtml        = (string)@file_get_contents($homePath);
        $this->descriptionHtml = (string)@file_get_contents($descriptionPath);
    }

    // ─── Home page ────────────────────────────────────────────────
    public function testHomeHasSearchDropdown(): void
    {
        $this->assertStringContainsString(
            'id="searchDropdown"',
            $this->homeHtml,
            'Recherche rapide live contient le dropdown'
        );
    }

    public function testHomeHasSearchInput(): void
    {
        $this->assertStringContainsString(
            'id="searchInput"',
            $this->homeHtml,
            'Recherche rapide live contient l\'input de recherche'
        );
    }

    public function testHomeHasLiveSearchFunction(): void
    {
        $this->assertStringContainsString(
            'function liveSearch',
            $this->homeHtml,
            'La recherche rapide live a bien une fonction JavaScript dédiée'
        );
    }

    public function testHomeHasSearchWrapper(): void
    {
        $this->assertStringContainsString(
            'search-wrapper',
            $this->homeHtml,
            'La page home contient le conteneur de recherche rapide'
        );
    }

    public function testHomeOrDescriptionHasGoToDescription(): void
    {
        $found = str_contains($this->homeHtml, 'onclick="goToDescription(')
               || str_contains($this->descriptionHtml, 'goToDescription(');

        $this->assertTrue($found, 'Les cards possèdent un lien vers la description du film');
    }

    // ─── Description page ─────────────────────────────────────────
    public function testDescriptionHasWatchButton(): void
    {
        $this->assertStringContainsString(
            'btn-watch',
            $this->descriptionHtml,
            'Bouton "Regarder" est présent sur la page description'
        );
    }

    public function testDescriptionWatchButtonUsesWatchAndOpen(): void
    {
        $this->assertStringContainsString(
            'onclick="watchAndOpen(',
            $this->descriptionHtml,
            'Bouton "Regarder" utilise watchAndOpen() pour ouvrir la vidéo'
        );
    }
}