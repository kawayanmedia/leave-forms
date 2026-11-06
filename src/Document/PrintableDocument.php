<?php

declare(strict_types=1);

namespace KawayanMedia\LeaveForms\Document;

/**
 * Marker for everything FormRenderer knows how to draw.
 */
interface PrintableDocument
{
    /**
     * Key of the layout (and of the template) the document is printed on.
     */
    public function layoutKey(): string;
}
