<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SetupCommand extends Command
{
    protected SymfonyStyle $io;
    protected string $vendorName;
    protected string $packageName;
    protected string $packageSlug;
    protected string $packageDescription;
    protected string $authorName;
    protected string $authorEmail;

    protected function configure(): void
    {
        $this->setName('setup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->newLine(1);
        $this->io->writeln('⏳ Setting up your package...');

        $this->vendorName = $this->ask("Vendor name?", 'esign');
        $this->packageSlug = $this->ask("Package name?");
        $this->packageName = str_after($this->packageSlug, 'laravel-');
        $this->packageDescription = $this->ask("Package description?");
        $this->authorName = $this->ask("Author name?", trim(shell_exec('git config user.name')));
        $this->authorEmail = $this->ask("Author name?", trim(shell_exec('git config user.email')));

        $replacements = $this->fileContentReplacements();
        $this->io->definitionList(
            ['Namespace' => $replacements[':vendor_namespace']],
            ['Package' => $replacements[':vendor'] . '/' . $replacements[':package_slug']],
            ['Author' => $replacements[':author_name'] . ' - ' . $replacements[':author_email']],
            ['Description' => $replacements[':package_description']],
        );

        if (! $this->io->confirm('This will replace all placeholders, continue?')) {
            $this->io->writeln('No actual changes have been made.');

            return self::FAILURE;
        }

        foreach ($this->getFiles() as $file) {
            foreach ($this->fileContentReplacements() as $placeholder => $replacement) {
                $this->replaceFileContent($placeholder, $replacement, $file);
            }
            foreach ($this->fileNameReplacements() as $placeholder => $replacement) {
                if (str_contains($file, $placeholder)) {
                    $this->replaceFileName($placeholder, $replacement, $file);
                }
            }
        }

        $this->io->newLine(1);
        $this->io->writeln('⏳ Installing composer dependencies...');
        shell_exec('cd .. && composer install && composer test');

        $this->io->newLine(1);
        $this->io->writeln('⏳ Running tests...');
        $this->io->writeln(shell_exec('cd .. && composer test'));

        $this->io->newLine(1);
        $this->io->writeln('⏳ Removing the setup directory');
        $this->removeSetupDirectory();

        $this->io->newLine(1);
        $this->io->writeln('✨ All done, go build something amazing');

        return self::SUCCESS;
    }

    protected function ask(string $question, ?string $default = null): mixed
    {
        return $this->io->ask($question, $default, function ($answer) {
            if (empty($answer)) {
                throw new RuntimeException('Required');
            }

            return $answer;
        });
    }

    protected function fileContentReplacements(): array
    {
        return [
            ':vendor_namespace_escaped' => str_studly($this->vendorName) . '\\\\' . str_studly($this->packageName),
            ':vendor_namespace' => str_studly($this->vendorName) . '\\' . str_studly($this->packageName),
            ':vendor' => strtolower($this->vendorName),
            ':studly_package_name' => str_studly($this->packageName),
            ':package_name' => strtolower($this->packageName),
            ':package_slug' => strtolower($this->packageSlug),
            ':package_description' => $this->packageDescription,
            ':author_name' => $this->authorName,
            ':author_email' => $this->authorEmail,
        ];
    }

    protected function fileNameReplacements(): array
    {
        return [
            'config/template.php' => 'config/' . $this->packageName . '.php',
            'src/Template.php' => 'src/' . str_studly($this->packageName) . '.php',
            'src/Facades/TemplateFacade.php' => 'src/Facades/' . str_studly($this->packageName) . 'Facade.php',
            'src/TemplateServiceProvider.php' => 'src/' . str_studly($this->packageName) . 'ServiceProvider.php',
            'tests/TemplateTest.php' => 'tests/' . str_studly($this->packageName) . 'Test.php',
        ];
    }

    protected function replaceFileContent(string $search, string $replace, string $file): void
    {
        file_put_contents(
            $file,
            str_replace($search, $replace, file_get_contents($file))
        );
    }

    protected function replaceFileName(string $search, string $replace, string $file): void
    {
        rename(
            $file,
            str_replace($search, $replace, $file)
        );
    }

    protected function getTemplateLocation(): string
    {
        return str_before_last($this->getSetupLocation(), 'setup');
    }

    protected function getSetupLocation(): string
    {
        return getcwd();
    }

    protected function removeSetupDirectory(): void
    {
        (new Filesystem())->remove($this->getSetupLocation());
    }

    protected function getFiles(): Finder
    {
        return (new Finder())
            ->files()
            ->ignoreDotFiles(false)
            ->exclude('setup')
            ->in($this->getTemplateLocation());
    }
}