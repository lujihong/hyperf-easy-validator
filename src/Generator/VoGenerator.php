<?php
declare(strict_types=1);

namespace Hyperf\EasyValidator\Generator;

use Hyperf\Utils\Filesystem\Filesystem;
use JetBrains\PhpStorm\Pure;

class VoGenerator
{
    /**
     * @var Filesystem
     */
    protected Filesystem $filesystem;
    protected string $codeContent;
    protected string $className;
    protected array $fields = [];
    protected string $stubDir;
    protected string $namespace;
    protected string $path;

    public function __construct(string $className, array $fields, string $namespace, string $path)
    {
        $this->filesystem = make(Filesystem::class);
        $this->setStubDir(__DIR__ . '/stubs');
        $this->setNamespace($namespace);
        $this->setClassName($className);
        $this->setField($fields);
        $this->setPath($path);
        $this->placeholderReplace();
        $this->generator();
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getStubDir(): string
    {
        return $this->stubDir;
    }

    public function setStubDir(string $stubDir): void
    {
        $this->stubDir = $stubDir;
    }

    public function setField(array $fields): void
    {
        $fields[] = ['name' => 'other', 'type' => 'array'];
        $this->fields = $fields;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace;
    }

    public function replace(): self
    {
        return $this;
    }

    protected function setClassName(string $className): void
    {
        $this->className = $className;
    }

    protected function getClassName(): string
    {
        return $this->className;
    }

    public function setCodeContent(string $content): void
    {
        $this->codeContent = $content;
    }

    public function getCodeContent(): string
    {
        return $this->codeContent;
    }

    #[Pure] protected function getTemplatePath(): string
    {
        return $this->getStubDir() . '/main.stub';
    }

    protected function readTemplate(): string
    {
        return $this->filesystem->sharedGet($this->getTemplatePath());
    }

    protected function getPlaceHolderContent(): array
    {
        return [
            '{NAMESPACE}',
            '{CLASS_NAME}',
            '{ATTRIBUTES}',
            '{FUNCTIONS}',
            '{USE_UPLOAD}'
        ];
    }

    protected function getReplaceContent(): array
    {
        return [
            $this->getNamespace(),
            $this->getClassName(),
            $this->getAttributes(),
            $this->getFunctions(),
            $this->getUseUploadString()
        ];
    }

    protected function getUseUploadString()
    {
        $str = '';
        foreach ($this->fields as $field) {
            if ($field['type'] === 'file') {
                $str = 'use Hyperf\HttpMessage\Upload\UploadedFile;';
            }
        }
        return $str;
    }

    protected function getAttributes(): string
    {
        $attrTemplate = $this->filesystem->sharedGet($this->getStubDir() . '/attr.stub');
        $str = '';
        foreach ($this->fields as $field) {
            $str .= str_replace(
                ['{ATTR}', '{TYPE}', '{DEFAULT_VALUE}'],
                [$field['name'], ($field['type'] === 'file' ? '?UploadedFile' : '?' . $field['type']), 'null'],
                $attrTemplate
            );
        }
        return $str;
    }

    protected function getFunctions(): string
    {
        $getTemplate = $this->filesystem->sharedGet($this->getStubDir() . '/get.stub');
        $setTemplate = $this->filesystem->sharedGet($this->getStubDir() . '/set.stub');

        $getString = $setString = '';
        foreach ($this->fields as $field) {
            $getString .= str_replace(
                ['{METHOD_NAME}', '{ATTR}', '{TYPE}'],
                [ucfirst($field['name']), $field['name'], ($field['type'] === 'file' ? '?UploadedFile' : '?' . $field['type'])],
                $getTemplate
            );

            $setString .= str_replace(
                ['{METHOD_NAME}', '{ATTR}', '{CLASS_NAME}', '{TYPE}'],
                [ucfirst($field['name']), $field['name'], 'self', ($field['type'] === 'file' ? 'UploadedFile' : $field['type'])],
                $setTemplate
            );
        }
        return $getString . $setString;
    }

    protected function placeholderReplace(): VoGenerator
    {
        $this->setCodeContent(str_replace(
            $this->getPlaceHolderContent(),
            $this->getReplaceContent(),
            $this->readTemplate()
        ));

        return $this;
    }

    public function generator(): void
    {
        $this->filesystem->exists($this->path) || $this->filesystem->makeDirectory($this->path, 0755, true, true);
        $this->filesystem->put($this->path . "{$this->getClassName()}.php", $this->replace()->getCodeContent());
    }

}