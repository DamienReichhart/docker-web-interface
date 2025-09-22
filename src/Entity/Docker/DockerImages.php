<?php

namespace App\Entity\Docker;

use App\Helper\DockerHelper;

class DockerImages
{
    private array $images;

    public function __construct()
    {
        $this->images = [];

        $output = DockerHelper::getInstance()->getImages();

        $idList = $this->parse($output);

        foreach ($idList as $id) {
            $this->images[] = new DockerSingleImage($id);
        }

    }

    final public function getImages(): array
    {
        return $this->images;
    }

    private function parse(string $output): array
    {
        $lines = explode("\n", trim($output));

        $imageIds = [];

        foreach ($lines as $index => $line) {
            if ($index === 0) {
                continue;
            }

            $columns = preg_split('/\s+/', $line);

            if (isset($columns[0])) {
                $imageIds[] = $columns[0];
            }
        }

        return $imageIds;
    }
}