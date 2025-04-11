<?php

namespace Aleksanm\Docx2txt;

use Aleksanm\Docx2txt\Exceptions\CouldNotExtractText;
use Aleksanm\Docx2txt\Exceptions\WordNotFound;
use Symfony\Component\Process\Process;

class Word
{
    protected string $word;
    
    protected string $binPath;
    
    protected array $options = [];
    
    public function __construct(string $binPath = null)
    {
        $this->binPath = $binPath ?? '/usr/bin/docx2txt';
    }
    
    public static function getText(string $word, string $binPath = null, array $options = []): string
    {
        return (new static($binPath))
            ->setOptions($options)
            ->setWord($word)
            ->text();
    }
    
    public function text(): string
    {
        $process = new Process(array_merge([$this->binPath], $this->options, [$this->word, '-']));
        $process->run();
        if (!$process->isSuccessful()) {
            throw new CouldNotExtractText($process);
        }
        
        return trim($process->getOutput(), " \t\n\r\0\x0B\x0C");
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
            if ($content[0] !== '-' ?? '') {
                $content = '-'.$content;
            }
            return explode(' ', $content, 2);
            
        };
        
        $reducer = function (array $carry, array $option): array {
            return array_merge($carry, $option);
        };
        
        return array_reduce(array_map($mapper, $options), $reducer, []);
    }
    
    public static function getDoc(string $word, string $binPath = null, array $options = []): string
    {
        return (new static('/usr/bin/docx2txt'))
            ->setOptions($options)
            ->setWord($word)
            ->doc();
    }
    
    public function doc(): string
    {
        $process = new Process(array_merge([$this->binPath], $this->options, [$this->word]));
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
