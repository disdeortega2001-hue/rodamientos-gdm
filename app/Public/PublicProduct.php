<?php

namespace App\Public;

use App\Enums\Availability;
use App\Models\Product;
use App\Support\Measures;
use App\Support\Money;

/** The only representation of a product that public pages and endpoints may receive. */
final class PublicProduct
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $slug,
        public readonly ?string $measures,
        public readonly string $description,
        public readonly string $category,
        public readonly string $categorySlug,
        public readonly ?int $priceCents,
        public readonly ?string $priceText,
        public readonly ?string $bsText,
        public readonly Availability $availability,
        public readonly ?string $imageUrl,
        public readonly bool $imageIsReference,
        public readonly array $specs,
    ) {}

    public static function fromModel(Product $p, ?string $rate): self
    {
        $image = $p->images->first();

        return new self(
            id: $p->id,
            code: $p->code,
            slug: $p->slug,
            measures: $p->measures,
            description: $p->description,
            category: $p->category->name,
            categorySlug: $p->category->slug,
            priceCents: $p->price_cents,
            priceText: $p->price_cents === null ? null : Money::usd($p->price_cents),
            bsText: $p->price_cents === null || $rate === null ? null : Money::bs($p->price_cents, $rate),
            availability: $p->availability(),
            imageUrl: $image?->url(),
            imageIsReference: (bool) $image?->is_reference,
            specs: Measures::labeled($p->measures),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'measures' => $this->measures,
            'description' => $this->description,
            'category' => $this->category,
            'price_cents' => $this->priceCents,
            'price_text' => $this->priceText,
            'bs_text' => $this->bsText,
            'availability' => $this->availability->value,
            'availability_label' => $this->availability->label(),
            'image_url' => $this->imageUrl,
            'image_is_reference' => $this->imageIsReference,
        ];
    }
}
