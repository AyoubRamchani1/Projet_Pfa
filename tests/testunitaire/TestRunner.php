<?php
// TestRunner.php

class TestRunner {
    private $passed = 0;
    private $failed = 0;
    private $total = 0;
    private $currentCategory = 'GÉNÉRAL';
    private $testDetails = [];

    public function setCategory(string $category) {
        $this->currentCategory = $category;
    }

    private function recordTest(bool $success, string $description, $expected, $actual) {
        $this->total++;
        if ($success) {
            $this->passed++;
        } else {
            $this->failed++;
        }

        // Détection automatique du type de test d'après le message
        $type = (strpos($description, '[FAIL ATTENDU]') !== false) ? 'FAILLE ATTENDUE' : 'NOMINAL';

        $this->testDetails[] = [
            'category'    => $this->currentCategory,
            'type'        => $type,
            'description' => $description,
            'status'      => $success ? 'SUCCESS' : 'FAILURE',
            'expected'    => $expected,
            'actual'      => $actual
        ];

        // Affichage en temps réel du résultat
        $statusSymbol = $success ? "✔ [OK]" : "❌ [FAIL]";
        echo sprintf(
            "%-17s | %-15s | %s\n", 
            $statusSymbol, 
            "[$type]", 
            $description
        );
        
        if (!$success) {
            echo "    └─ Attendu: " . json_encode($expected) . " | Obtenu: " . json_encode($actual) . "\n";
        }
    }

    public function assertEquals($expected, $actual, string $description = '') {
        $this->recordTest($expected === $actual, $description, $expected, $actual);
    }

    public function assertTrue($condition, string $description = '') {
        $this->recordTest($condition === true, $description, true, $condition);
    }

    public function summary(): bool {
        echo "\n" . str_repeat("═", 70) . "\n";
        echo " 📊 RÉSUMÉ DES RÉSULTATS DU TEST\n";
        echo str_repeat("═", 70) . "\n";
        
        echo sprintf(" Total des assertions exécutées : %d\n", $this->total);
        echo sprintf("   🟢 Réussies                 : %d\n", $this->passed);
        echo sprintf("   🔴 Échouées                 : %d\n", $this->failed);
        echo str_repeat("─", 70) . "\n";
        
        if ($this->failed > 0) {
            echo " ⚠️  Verdict : La suite de tests contient des échecs (Failles détectées).\n";
        } else {
            echo " ✅ Verdict : Tous les tests passent avec succès.\n";
        }
        echo str_repeat("═", 70) . "\n\n";

        // On réinitialise les compteurs pour la suite si summary() est rappelé au milieu
        return $this->failed === 0;
    }
}