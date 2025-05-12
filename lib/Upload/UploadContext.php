<?php
namespace Kawf\Upload;

class UploadContext {
    private array $config;
    private string $filepath;
    private array $fileMetadata;
    private int $userId;
    private string $namespace;
    private ?int $messageId = null;

    public function __construct(
        array $config,
        string $filepath,
        array $fileMetadata,
        int $userId,
        string $namespace
    ) {
        $this->config = $config;
        $this->filepath = $filepath;
        $this->fileMetadata = $fileMetadata;
        $this->userId = $userId;
        $this->namespace = $namespace;
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function getFilepath(): string {
        return $this->filepath;
    }

    public function getFileMetadata(): array {
        return $this->fileMetadata;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getNamespace(): string {
        return $this->namespace;
    }

    public function setMessageContext(int $messageId): void {
        $this->messageId = $messageId;
    }

    public function getMessageId(): ?int {
        return $this->messageId;
    }

    public function createMetadata(): ImageMetadata {
        return ImageMetadata::createMetadata(
            $this->filepath,
            $this->fileMetadata,
            $this->userId
        );
    }
}
// vim: set ts=8 sw=4 et:
