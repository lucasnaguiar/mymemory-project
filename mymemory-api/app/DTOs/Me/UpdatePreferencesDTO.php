<?php

namespace App\DTOs\Me;

final class UpdatePreferencesDTO
{
    public function __construct(
        public readonly ?string $aiLevelText,
        public readonly ?string $aiLevelUrl,
        public readonly ?string $aiLevelImage,
        public readonly ?string $aiLevelAudio,
        public readonly ?string $aiLevelVideo,
        public readonly ?string $aiLevelDocument,
        public readonly ?bool $confirmBeforeProcessing,
        public readonly ?bool $soundEnabled,
        public readonly ?int $ocrCorrectionThreshold,
        public readonly bool $clearOcrThreshold = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            aiLevelText: $data['ai_level_text'] ?? null,
            aiLevelUrl: $data['ai_level_url'] ?? null,
            aiLevelImage: $data['ai_level_image'] ?? null,
            aiLevelAudio: $data['ai_level_audio'] ?? null,
            aiLevelVideo: $data['ai_level_video'] ?? null,
            aiLevelDocument: $data['ai_level_document'] ?? null,
            confirmBeforeProcessing: isset($data['confirm_before_processing'])
                ? (bool) $data['confirm_before_processing']
                : null,
            soundEnabled: isset($data['sound_enabled'])
                ? (bool) $data['sound_enabled']
                : null,
            ocrCorrectionThreshold: array_key_exists('ocr_correction_threshold', $data) && $data['ocr_correction_threshold'] !== null
                ? (int) $data['ocr_correction_threshold']
                : null,
            clearOcrThreshold: array_key_exists('ocr_correction_threshold', $data)
                && $data['ocr_correction_threshold'] === null,
        );
    }

    public function toUpdateArray(): array
    {
        $fields = [];

        if ($this->aiLevelText !== null) $fields['ai_level_text'] = $this->aiLevelText;
        if ($this->aiLevelUrl !== null) $fields['ai_level_url'] = $this->aiLevelUrl;
        if ($this->aiLevelImage !== null) $fields['ai_level_image'] = $this->aiLevelImage;
        if ($this->aiLevelAudio !== null) $fields['ai_level_audio'] = $this->aiLevelAudio;
        if ($this->aiLevelVideo !== null) $fields['ai_level_video'] = $this->aiLevelVideo;
        if ($this->aiLevelDocument !== null) $fields['ai_level_document'] = $this->aiLevelDocument;
        if ($this->confirmBeforeProcessing !== null) $fields['confirm_before_processing'] = $this->confirmBeforeProcessing;
        if ($this->soundEnabled !== null) $fields['sound_enabled'] = $this->soundEnabled;

        if ($this->clearOcrThreshold) {
            $fields['ocr_correction_threshold'] = null;
        } elseif ($this->ocrCorrectionThreshold !== null) {
            $fields['ocr_correction_threshold'] = $this->ocrCorrectionThreshold;
        }

        return $fields;
    }
}
