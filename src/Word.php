<?php

namespace Aleksanm\Docx2txt;

use Aleksanm\Docx2txt\Exceptions\CouldNotExtractText;
use Aleksanm\Docx2txt\Exceptions\WordNotFound;
use Symfony\Component\Process\Process;

class Word
{
    protected ?string $word = null;
    
    protected string $binPath;
    
    protected array $options = [];
    
    public function __construct(?string $binPath = null)
    {
        $this->binPath = $binPath ?? '/usr/bin/docx2txt';
    }
    
    public static function getText(string $word, ?string $binPath = null, array $options = []): string
    {
        return (new static($binPath))
            ->setOptions($options)
            ->setWord($word)
            ->text();
    }
    
    public function text(): string
    {
        $this->ensureWordIsSet();

        // Check if '-' is already in options to avoid duplication
        $hasStdoutOption = in_array('-', $this->options);
        $command = array_merge([$this->binPath], [$this->word], $this->options);

        if (!$hasStdoutOption) {
            $command[] = '-';
        }

        return $this->runProcess($command);
    }
    
    public function setWord(string $word): self
    {
        if (!is_readable($word)) {
            throw new WordNotFound("Word {$word} is not readable");
        }
        $this->word = $word;
        
        return $this;
    }
    
    public function setOptions(array $options): self
    {
        $this->options = $this->parseOptions($options);
        return $this;
    }
    
    public function parseOptions(array $options): array
    {
        $mapper = function (string $content): array {
            $content = trim($content);
            if (empty($content)) {
                return [];
            }
            if ($content[0] !== '-') {
                $content = '-'.$content;
            }
            // Handle single character options like '-'
            if ($content === '-') {
                return ['-'];
            }
            return explode(' ', $content, 2);
        };
        
        $reducer = function (array $carry, array $option): array {
            return array_merge($carry, $option);
        };
        
        return array_reduce(array_map($mapper, $options), $reducer, []);
    }
    
    public static function getDoc(string $word, ?string $binPath = null, array $options = []): string
    {
        return (new static($binPath))
            ->setOptions($options)
            ->setWord($word)
            ->doc();
    }

    public function doc(): string
    {
        $this->ensureWordIsSet();

        return $this->runProcess(array_merge([$this->binPath], $this->options, [$this->word]));
    }

    protected function ensureWordIsSet(): void
    {
        if ($this->word === null) {
            throw new WordNotFound('No word file has been set. Call setWord() first.');
        }
    }

    protected function runProcess(array $command): string
    {
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new CouldNotExtractText($process);
        }

        return trim($process->getOutput(), " \t\n\r\0\x0B\x0C");
    }
    
    public function addOptions(array $options): self
    {
        $this->options = array_merge(
            $this->options,
            $this->parseOptions($options)
        );
        
        return $this;
    }
}
