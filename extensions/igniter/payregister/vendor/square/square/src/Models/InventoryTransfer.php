<?php

declare(strict_types=1);

namespace Square\Models;

use stdClass;

/**
 * Represents the transfer of a quantity of product inventory at a
 * particular time from one location to another.
 */
class InventoryTransfer implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var array
     */
    private $referenceId = [];

    /**
     * @var string|null
     */
    private $state;

    /**
     * @var array
     */
    private $fromLocationId = [];

    /**
     * @var array
     */
    private $toLocationId = [];

    /**
     * @var array
     */
    private $catalogObjectId = [];

    /**
     * @var array
     */
    private $catalogObjectType = [];

    /**
     * @var array
     */
    private $quantity = [];

    /**
     * @var array
     */
    private $occurredAt = [];

    /**
     * @var string|null
     */
    private $createdAt;

    /**
     * @var SourceApplication|null
     */
    private $source;

    /**
     * @var array
     */
    private $employeeId = [];

    /**
     * @var array
     */
    private $teamMemberId = [];

    /**
     * Returns Id.
     * A unique ID generated by Square for the
     * `InventoryTransfer`.
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Sets Id.
     * A unique ID generated by Square for the
     * `InventoryTransfer`.
     *
     * @maps id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * Returns Reference Id.
     * An optional ID provided by the application to tie the
     * `InventoryTransfer` to an external system.
     */
    public function getReferenceId(): ?string
    {
        if (count($this->referenceId) == 0) {
            return null;
        }
        return $this->referenceId['value'];
    }

    /**
     * Sets Reference Id.
     * An optional ID provided by the application to tie the
     * `InventoryTransfer` to an external system.
     *
     * @maps reference_id
     */
    public function setReferenceId(?string $referenceId): void
    {
        $this->referenceId['value'] = $referenceId;
    }

    /**
     * Unsets Reference Id.
     * An optional ID provided by the application to tie the
     * `InventoryTransfer` to an external system.
     */
    public function unsetReferenceId(): void
    {
        $this->referenceId = [];
    }

    /**
     * Returns State.
     * Indicates the state of a tracked item quantity in the lifecycle of goods.
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Sets State.
     * Indicates the state of a tracked item quantity in the lifecycle of goods.
     *
     * @maps state
     */
    public function setState(?string $state): void
    {
        $this->state = $state;
    }

    /**
     * Returns From Location Id.
     * The Square-generated ID of the [Location]($m/Location) where the related
     * quantity of items was tracked before the transfer.
     */
    public function getFromLocationId(): ?string
    {
        if (count($this->fromLocationId) == 0) {
            return null;
        }
        return $this->fromLocationId['value'];
    }

    /**
     * Sets From Location Id.
     * The Square-generated ID of the [Location]($m/Location) where the related
     * quantity of items was tracked before the transfer.
     *
     * @maps from_location_id
     */
    public function setFromLocationId(?string $fromLocationId): void
    {
        $this->fromLocationId['value'] = $fromLocationId;
    }

    /**
     * Unsets From Location Id.
     * The Square-generated ID of the [Location]($m/Location) where the related
     * quantity of items was tracked before the transfer.
     */
    public function unsetFromLocationId(): void
    {
        $this->fromLocationId = [];
    }

    /**
     * Returns To Location Id.
     * The Square-generated ID of the [Location]($m/Location) where the related
     * quantity of items was tracked after the transfer.
     */
    public function getToLocationId(): ?string
    {
        if (count($this->toLocationId) == 0) {
            return null;
        }
        return $this->toLocationId['value'];
    }

    /**
     * Sets To Location Id.
     * The Square-generated ID of the [Location]($m/Location) where the related
     * quantity of items was tracked after the transfer.
     *
     * @maps to_location_id
     */
    public function setToLocationId(?string $toLocationId): void
    {
        $this->toLocationId['value'] = $toLocationId;
    }

    /**
     * Unsets To Location Id.
     * The Square-generated ID of the [Location]($m/Location) where the related
     * quantity of items was tracked after the transfer.
     */
    public function unsetToLocationId(): void
    {
        $this->toLocationId = [];
    }

    /**
     * Returns Catalog Object Id.
     * The Square-generated ID of the
     * [CatalogObject]($m/CatalogObject) being tracked.
     */
    public function getCatalogObjectId(): ?string
    {
        if (count($this->catalogObjectId) == 0) {
            return null;
        }
        return $this->catalogObjectId['value'];
    }

    /**
     * Sets Catalog Object Id.
     * The Square-generated ID of the
     * [CatalogObject]($m/CatalogObject) being tracked.
     *
     * @maps catalog_object_id
     */
    public function setCatalogObjectId(?string $catalogObjectId): void
    {
        $this->catalogObjectId['value'] = $catalogObjectId;
    }

    /**
     * Unsets Catalog Object Id.
     * The Square-generated ID of the
     * [CatalogObject]($m/CatalogObject) being tracked.
     */
    public function unsetCatalogObjectId(): void
    {
        $this->catalogObjectId = [];
    }

    /**
     * Returns Catalog Object Type.
     * The [type]($m/CatalogObjectType) of the [CatalogObject]($m/CatalogObject) being tracked.
     *
     * The Inventory API supports setting and reading the `"catalog_object_type": "ITEM_VARIATION"` field
     * value.
     * In addition, it can also read the `"catalog_object_type": "ITEM"` field value that is set by the
     * Square Restaurants app.
     */
    public function getCatalogObjectType(): ?string
    {
        if (count($this->catalogObjectType) == 0) {
            return null;
        }
        return $this->catalogObjectType['value'];
    }

    /**
     * Sets Catalog Object Type.
     * The [type]($m/CatalogObjectType) of the [CatalogObject]($m/CatalogObject) being tracked.
     *
     * The Inventory API supports setting and reading the `"catalog_object_type": "ITEM_VARIATION"` field
     * value.
     * In addition, it can also read the `"catalog_object_type": "ITEM"` field value that is set by the
     * Square Restaurants app.
     *
     * @maps catalog_object_type
     */
    public function setCatalogObjectType(?string $catalogObjectType): void
    {
        $this->catalogObjectType['value'] = $catalogObjectType;
    }

    /**
     * Unsets Catalog Object Type.
     * The [type]($m/CatalogObjectType) of the [CatalogObject]($m/CatalogObject) being tracked.
     *
     * The Inventory API supports setting and reading the `"catalog_object_type": "ITEM_VARIATION"` field
     * value.
     * In addition, it can also read the `"catalog_object_type": "ITEM"` field value that is set by the
     * Square Restaurants app.
     */
    public function unsetCatalogObjectType(): void
    {
        $this->catalogObjectType = [];
    }

    /**
     * Returns Quantity.
     * The number of items affected by the transfer as a decimal string.
     * Can support up to 5 digits after the decimal point.
     */
    public function getQuantity(): ?string
    {
        if (count($this->quantity) == 0) {
            return null;
        }
        return $this->quantity['value'];
    }

    /**
     * Sets Quantity.
     * The number of items affected by the transfer as a decimal string.
     * Can support up to 5 digits after the decimal point.
     *
     * @maps quantity
     */
    public function setQuantity(?string $quantity): void
    {
        $this->quantity['value'] = $quantity;
    }

    /**
     * Unsets Quantity.
     * The number of items affected by the transfer as a decimal string.
     * Can support up to 5 digits after the decimal point.
     */
    public function unsetQuantity(): void
    {
        $this->quantity = [];
    }

    /**
     * Returns Occurred At.
     * A client-generated RFC 3339-formatted timestamp that indicates when
     * the transfer took place. For write actions, the `occurred_at` timestamp
     * cannot be older than 24 hours or in the future relative to the time of the
     * request.
     */
    public function getOccurredAt(): ?string
    {
        if (count($this->occurredAt) == 0) {
            return null;
        }
        return $this->occurredAt['value'];
    }

    /**
     * Sets Occurred At.
     * A client-generated RFC 3339-formatted timestamp that indicates when
     * the transfer took place. For write actions, the `occurred_at` timestamp
     * cannot be older than 24 hours or in the future relative to the time of the
     * request.
     *
     * @maps occurred_at
     */
    public function setOccurredAt(?string $occurredAt): void
    {
        $this->occurredAt['value'] = $occurredAt;
    }

    /**
     * Unsets Occurred At.
     * A client-generated RFC 3339-formatted timestamp that indicates when
     * the transfer took place. For write actions, the `occurred_at` timestamp
     * cannot be older than 24 hours or in the future relative to the time of the
     * request.
     */
    public function unsetOccurredAt(): void
    {
        $this->occurredAt = [];
    }

    /**
     * Returns Created At.
     * An RFC 3339-formatted timestamp that indicates when Square
     * received the transfer request.
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Sets Created At.
     * An RFC 3339-formatted timestamp that indicates when Square
     * received the transfer request.
     *
     * @maps created_at
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Returns Source.
     * Represents information about the application used to generate a change.
     */
    public function getSource(): ?SourceApplication
    {
        return $this->source;
    }

    /**
     * Sets Source.
     * Represents information about the application used to generate a change.
     *
     * @maps source
     */
    public function setSource(?SourceApplication $source): void
    {
        $this->source = $source;
    }

    /**
     * Returns Employee Id.
     * The Square-generated ID of the [Employee]($m/Employee) responsible for the
     * inventory transfer.
     */
    public function getEmployeeId(): ?string
    {
        if (count($this->employeeId) == 0) {
            return null;
        }
        return $this->employeeId['value'];
    }

    /**
     * Sets Employee Id.
     * The Square-generated ID of the [Employee]($m/Employee) responsible for the
     * inventory transfer.
     *
     * @maps employee_id
     */
    public function setEmployeeId(?string $employeeId): void
    {
        $this->employeeId['value'] = $employeeId;
    }

    /**
     * Unsets Employee Id.
     * The Square-generated ID of the [Employee]($m/Employee) responsible for the
     * inventory transfer.
     */
    public function unsetEmployeeId(): void
    {
        $this->employeeId = [];
    }

    /**
     * Returns Team Member Id.
     * The Square-generated ID of the [Team Member]($m/TeamMember) responsible for the
     * inventory transfer.
     */
    public function getTeamMemberId(): ?string
    {
        if (count($this->teamMemberId) == 0) {
            return null;
        }
        return $this->teamMemberId['value'];
    }

    /**
     * Sets Team Member Id.
     * The Square-generated ID of the [Team Member]($m/TeamMember) responsible for the
     * inventory transfer.
     *
     * @maps team_member_id
     */
    public function setTeamMemberId(?string $teamMemberId): void
    {
        $this->teamMemberId['value'] = $teamMemberId;
    }

    /**
     * Unsets Team Member Id.
     * The Square-generated ID of the [Team Member]($m/TeamMember) responsible for the
     * inventory transfer.
     */
    public function unsetTeamMemberId(): void
    {
        $this->teamMemberId = [];
    }

    /**
     * Encode this object to JSON
     *
     * @param bool $asArrayWhenEmpty Whether to serialize this model as an array whenever no fields
     *        are set. (default: false)
     *
     * @return array|stdClass
     */
    #[\ReturnTypeWillChange] // @phan-suppress-current-line PhanUndeclaredClassAttribute for (php < 8.1)
    public function jsonSerialize(bool $asArrayWhenEmpty = false)
    {
        $json = [];
        if (isset($this->id)) {
            $json['id']                  = $this->id;
        }
        if (!empty($this->referenceId)) {
            $json['reference_id']        = $this->referenceId['value'];
        }
        if (isset($this->state)) {
            $json['state']               = $this->state;
        }
        if (!empty($this->fromLocationId)) {
            $json['from_location_id']    = $this->fromLocationId['value'];
        }
        if (!empty($this->toLocationId)) {
            $json['to_location_id']      = $this->toLocationId['value'];
        }
        if (!empty($this->catalogObjectId)) {
            $json['catalog_object_id']   = $this->catalogObjectId['value'];
        }
        if (!empty($this->catalogObjectType)) {
            $json['catalog_object_type'] = $this->catalogObjectType['value'];
        }
        if (!empty($this->quantity)) {
            $json['quantity']            = $this->quantity['value'];
        }
        if (!empty($this->occurredAt)) {
            $json['occurred_at']         = $this->occurredAt['value'];
        }
        if (isset($this->createdAt)) {
            $json['created_at']          = $this->createdAt;
        }
        if (isset($this->source)) {
            $json['source']              = $this->source;
        }
        if (!empty($this->employeeId)) {
            $json['employee_id']         = $this->employeeId['value'];
        }
        if (!empty($this->teamMemberId)) {
            $json['team_member_id']      = $this->teamMemberId['value'];
        }
        $json = array_filter($json, function ($val) {
            return $val !== null;
        });

        return (!$asArrayWhenEmpty && empty($json)) ? new stdClass() : $json;
    }
}
