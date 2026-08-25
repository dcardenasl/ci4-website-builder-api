<?php

declare(strict_types=1);

namespace App\DTO\Request\Files;

use App\DTO\Request\Support\TracksProvidedFields;
use dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO;
use dcardenasl\Ci4ApiCore\Exceptions\BadRequestException;

readonly class UpdateFileMetadataRequestDTO extends BaseRequestDTO
{
    use TracksProvidedFields;

    public ?string $original_name;
    public ?string $alt_text;
    public ?string $caption;
    public ?string $credit;
    public ?string $category;

    public function rules(): array
    {
        return [
            'original_name' => 'permit_empty|string|max_length[255]',
            'alt_text'      => 'permit_empty|string|max_length[255]',
            'caption'       => 'permit_empty|string|max_length[65535]',
            'credit'        => 'permit_empty|string|max_length[255]',
            'category'      => 'permit_empty|in_list[document,image,video,audio]',
        ];
    }

    protected function map(array $data): void
    {
        $this->trackProvidedFields($data);
        $knownFields = ['original_name', 'alt_text', 'caption', 'credit', 'category'];
        $provided = array_filter($knownFields, fn (string $field): bool => array_key_exists($field, $data));

        if ($provided === []) {
            throw new BadRequestException(lang('Files.metadata_no_fields'));
        }

        $this->original_name = isset($data['original_name']) && $data['original_name'] !== '' ? trim((string) $data['original_name']) : null;
        $this->alt_text      = isset($data['alt_text']) && $data['alt_text'] !== '' ? trim((string) $data['alt_text']) : null;
        $this->caption       = isset($data['caption']) && $data['caption'] !== '' ? trim((string) $data['caption']) : null;
        $this->credit        = isset($data['credit']) && $data['credit'] !== '' ? trim((string) $data['credit']) : null;
        $this->category      = isset($data['category']) && $data['category'] !== '' ? trim((string) $data['category']) : null;
    }

    public function toArray(): array
    {
        return $this->filterProvidedFields([
            'original_name' => $this->original_name,
            'alt_text'      => $this->alt_text,
            'caption'       => $this->caption,
            'credit'        => $this->credit,
            'category'      => $this->category,
        ]);
    }
}
