<?php

namespace Bootstrap5Migrator\Services;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class ProgressService
{
    protected ?ProgressBar $progressBar = null;

    protected array $stats = [];

    protected float $startTime;

    protected string $currentTask = '';

    public function __construct(protected OutputInterface $output)
    {
        $this->startTime = microtime(true);
    }

    /**
     * Démarre une barre de progression avec style personnalisé
     */
    public function start(int $max, string $task = ''): self
    {
        $this->currentTask = $task;
        $this->progressBar = new ProgressBar($this->output, $max);

        // Style personnalisé avec emoji et couleurs
        $this->progressBar->setBarCharacter('<fg=green>━</>');
        $this->progressBar->setEmptyBarCharacter('<fg=red>━</>');
        $this->progressBar->setProgressCharacter('<fg=green>🚀</>');

        // Format personnalisé avec informations détaillées
        $this->progressBar->setFormat($this->getProgressFormat());

        if ($task !== '' && $task !== '0') {
            $this->output->writeln(\sprintf('<fg=cyan>🔄 %s</>', $task));
        }

        $this->progressBar->start();

        return $this;
    }

    /**
     * Avance la progression avec message optionnel
     */
    public function advance(int $step = 1, string $message = ''): self
    {
        if (! $this->progressBar instanceof ProgressBar) {
            return $this;
        }

        if ($message !== '' && $message !== '0') {
            $this->progressBar->setMessage($message, 'status');
        }

        $this->progressBar->advance($step);

        return $this;
    }

    /**
     * Met à jour le message de statut
     */
    public function setStatus(string $message): self
    {
        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->setMessage($message, 'status');
            $this->progressBar->display();
        }

        return $this;
    }

    /**
     * Termine la barre de progression
     */
    public function finish(string $completionMessage = ''): self
    {
        if ($this->progressBar instanceof ProgressBar) {
            $this->progressBar->finish();
            $this->output->writeln('');

            if ($completionMessage !== '' && $completionMessage !== '0') {
                $this->output->writeln(\sprintf('<fg=green>✅ %s</>', $completionMessage));
            }
        }

        return $this;
    }

    /**
     * Affiche un message d'étape avec style
     */
    public function step(string $message, string $emoji = '🔄'): self
    {
        $this->output->writeln(\sprintf('<fg=yellow>%s %s</>', $emoji, $message));

        return $this;
    }

    /**
     * Affiche un message de succès
     */
    public function success(string $message): self
    {
        $this->output->writeln(\sprintf('<fg=green>✅ %s</>', $message));

        return $this;
    }

    /**
     * Affiche un message d'erreur
     */
    public function error(string $message): self
    {
        $this->output->writeln(\sprintf('<fg=red>❌ %s</>', $message));

        return $this;
    }

    /**
     * Affiche un avertissement
     */
    public function warning(string $message): self
    {
        $this->output->writeln(\sprintf('<fg=yellow>⚠️  %s</>', $message));

        return $this;
    }

    /**
     * Affiche des informations
     */
    public function info(string $message): self
    {
        $this->output->writeln(\sprintf('<fg=blue>ℹ️  %s</>', $message));

        return $this;
    }

    /**
     * Crée une progression multi-étapes
     */
    public function multiStep(array $steps): MultiStepProgress
    {
        return new MultiStepProgress($this->output, $steps);
    }

    /**
     * Affiche les statistiques de performance
     */
    public function displayStats(array $stats): self
    {
        $elapsedTime = microtime(true) - $this->startTime;

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan>📊 Statistiques de performance:</>');
        $this->output->writeln('┌─────────────────────────────────────────┐');

        foreach ($stats as $label => $value) {
            $formattedLabel = str_pad($label, 20);
            $this->output->writeln(\sprintf('│ %s : <fg=green>%s</> │', $formattedLabel, $value));
        }

        $formattedTime = str_pad('Temps total', 20);
        $this->output->writeln(\sprintf('│ %s : <fg=green>', $formattedTime).round($elapsedTime, 2).'s</> │');
        $this->output->writeln('└─────────────────────────────────────────┘');

        return $this;
    }

    /**
     * Affiche un tableau formaté
     */
    public function table(array $headers, array $rows): self
    {
        $terminal = new Terminal();
        $width = $terminal->getWidth();
        $colWidth = \intval(($width - \count($headers) - 1) / \count($headers));

        // Header
        $this->output->writeln('┌'.str_repeat('─', $width - 2).'┐');
        $headerRow = '│';

        foreach ($headers as $header) {
            $headerRow .= ' <fg=cyan>'.str_pad((string) $header, $colWidth - 1).'</> │';
        }

        $this->output->writeln($headerRow);
        $this->output->writeln('├'.str_repeat('─', $width - 2).'┤');

        // Rows
        foreach ($rows as $row) {
            $rowStr = '│';

            foreach ($row as $i => $cell) {
                $color = isset($headers[$i]) && $headers[$i] === 'Statut' ? $this->getStatusColor($cell) : 'white';
                $rowStr .= ' <fg='.$color.'>'.str_pad((string) $cell, $colWidth - 1).'</> │';
            }

            $this->output->writeln($rowStr);
        }

        $this->output->writeln('└'.str_repeat('─', $width - 2).'┘');

        return $this;
    }

    /**
     * Retourne le format de la barre de progression
     */
    protected function getProgressFormat(): string
    {
        return '%current%/%max% [%bar%] %percent:3s%% 🕐 %elapsed:6s%/%estimated:-6s% %memory:6s% | %status%';
    }

    /**
     * Retourne la couleur selon le statut
     */
    protected function getStatusColor(string $status): string
    {
        $statusColors = [
            '✅' => 'green',
            '❌' => 'red',
            '⚠️' => 'yellow',
            'OK' => 'green',
            'ERREUR' => 'red',
            'ATTENTION' => 'yellow',
        ];

        foreach ($statusColors as $pattern => $color) {
            if (stripos($status, $pattern) !== false) {
                return $color;
            }
        }

        return 'white';
    }
}

/**
 * Classe pour gérer les progressions multi-étapes
 */
class MultiStepProgress
{
    protected int $currentStep = 0;

    protected ?ProgressBar $overallProgress = null;

    public function __construct(protected OutputInterface $output, protected array $steps) {}

    /**
     * Démarre la progression multi-étapes
     */
    public function start(): self
    {
        $this->output->writeln('<fg=cyan>🚀 Démarrage de la migration Bootstrap 5...</>');
        $this->output->writeln('');

        $this->overallProgress = new ProgressBar($this->output, \count($this->steps));
        $this->overallProgress->setFormat('Progression globale: %current%/%max% [%bar%] %percent:3s%%');
        $this->overallProgress->start();

        $this->output->writeln('');
        $this->output->writeln('');

        return $this;
    }

    /**
     * Exécute l'étape suivante
     */
    public function nextStep(?callable $callback = null): self
    {
        if ($this->currentStep >= \count($this->steps)) {
            return $this;
        }

        $step = $this->steps[$this->currentStep];
        $stepNumber = $this->currentStep + 1;

        $this->output->writeln(\sprintf('<fg=yellow>📋 Étape %d/%d: %s</>', $stepNumber, \count($this->steps), $step['title']));

        if (isset($step['description'])) {
            $this->output->writeln(\sprintf('   <fg=gray>%s</>', $step['description']));
        }

        if ($callback !== null) {
            $callback($step, $stepNumber);
        }

        $this->currentStep++;

        if ($this->overallProgress instanceof ProgressBar) {
            $this->overallProgress->advance();
        }

        $this->output->writeln('');

        return $this;
    }

    /**
     * Termine la progression multi-étapes
     */
    public function finish(): self
    {
        if ($this->overallProgress instanceof ProgressBar) {
            $this->overallProgress->finish();
            $this->output->writeln('');
        }

        $this->output->writeln('<fg=green>🎉 Migration terminée avec succès !</>');

        return $this;
    }

    /**
     * Marque une étape comme échouée
     */
    public function failStep(string $reason): self
    {
        $this->output->writeln(\sprintf("<fg=red>💥 Échec de l'étape: %s</>", $reason));

        return $this;
    }
}
