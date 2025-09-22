<?php /** @noinspection PhpUnused */

/** @noinspection PhpUndefinedFieldInspection */

namespace App\Entity\Docker;

use App\Helper\DockerHelper;
use JsonException;

class DockerSingleImage
{
    private string $repository;
    private string $tag;
    private string $imageId;
    private string $createdAt;
    private string $size;
    private array $digest;

    /**
     * @throws JsonException
     */
    final public function __construct(string $repoTags)
    {
        $imageInspect = new DockerImageInspect($repoTags);
        $imageTagsExploded = explode(':', $imageInspect->RepoTags[0]);

        $this->repository = $imageTagsExploded[0];
        $this->tag = $imageTagsExploded[1] ?? 'latest';
        $this->imageId = $imageInspect->Id;
        $this->createdAt = $imageInspect->Created;
        $this->size = $imageInspect->Size;
        $this->digest = $imageInspect->RepoDigests;
    }

    final public static function pull(string $repoWithTag): void
    {
        $helper = DockerHelper::getInstance();
        $helper->pullImage($repoWithTag);
    }

    final public function getRepository(): string
    {
        return $this->repository;
    }

    final public function getTag(): string
    {
        return $this->tag;
    }

    final public function getImageId(): string
    {
        return $this->imageId;
    }

    final public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    final public function getSize(): string
    {
        return $this->size;
    }

    final public function getDigest(): array
    {
        return $this->digest;
    }

    /**
     * @throws JsonException
     */
    final public function getInspect(): DockerImageInspect
    {
        return new DockerImageInspect($this->imageId);
    }

    final public function getRepoWithTag(): string
    {
        return $this->repository . ':' . $this->tag;
    }

    final public function toArray(): array
    {
        return [
            'repository' => $this->repository,
            'tag' => $this->tag,
            'imageId' => $this->imageId,
            'createdAt' => $this->createdAt,
            'size' => $this->size,
            'digest' => $this->digest,
        ];
    }

    public function __toString(): string
    {
        return $this->getRepoWithTag();
    }

}