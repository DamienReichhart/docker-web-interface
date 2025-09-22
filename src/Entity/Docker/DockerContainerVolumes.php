<?php /** @noinspection PhpUnused */

namespace App\Entity\Docker;

use Exception;
use RuntimeException;

class DockerContainerVolumes
{
    private array $volumes;

    public function __construct(array $volumes)
    {
        $this->volumes = [];

        foreach ($volumes as $volume) {
            $explodedVolume = explode(':', $volume);
            $this->volumes[] = new DockerSingleContainerVolume($explodedVolume[0], $explodedVolume[1]);
        }
    }

    public static function fromStdClass(array $Mounts): DockerContainerVolumes
    {
        $arrayVolumes = self::transformStdClassVolumeArray($Mounts);
        return new self($arrayVolumes);
    }

    private static function transformStdClassVolumeArray(array $volumes): array
    {
        $result = [];

        foreach ($volumes as $volume) {
            // Check if both Source and Destination exist in the object
            if (isset($volume->Source, $volume->Destination)) {
                $result[] = $volume->Source . ':' . $volume->Destination;
            }
        }

        return $result;
    }

    final public function getVolumes(): array
    {
        return $this->volumes;
    }

    final public function addVolume(mixed $sourceVolume, mixed $targetVolume): void
    {
        $this->volumes[] = new DockerSingleContainerVolume($sourceVolume, $targetVolume);
    }

    final public function getAllVolumes(): array
    {
        return $this->getVolumes();
    }


    final public function removeVolumeByIndex(int $index): void
    {
        unset($this->volumes[$index]);
    }

    /**
     * @throws Exception
     */
    final public function removeVolume(string $sourceVolume, string $targetVolume): void
    {
        foreach ($this->volumes as $key => $volume) {
            if ($volume->getSourceVolume() === $sourceVolume && $volume->getTargetVolume() === $targetVolume) {
                unset($this->volumes[$key]);
                // Reindex the array to maintain consistency
                $this->volumes = array_values($this->volumes);
                return;
            }
        }
        throw new RuntimeException("Volume $sourceVolume:$targetVolume not found.");
    }

}