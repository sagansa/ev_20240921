<?php

namespace App\Services;

use App\Models\SpkluScrapeRaw;

/**
 * Reviews scraped SPKLU rows.
 *
 * Design rule (per project owner): spklu_locations is the canonical
 * JSON-imported dataset and is treated as fixed — it changes ONLY through the
 * JSON replace flow. Scraping is a SEPARATE display layer that lives in
 * spklu_scrape_raw. This service therefore never writes to spklu_locations or
 * spklu_charger_boxes; it only mutates staging rows (status + optional link).
 *
 * The mobile API unions APPROVED scrape rows with spklu_locations at query
 * time, so a scraped place appears on the map without being inserted into the
 * canonical table.
 */
class SpkluScrapeMergeService
{
    /**
     * Mark a scraped row as approved (will appear on the map via the UNION
     * in the API). Optionally link it to a production location id for
     * reference only — linking does NOT touch the production row.
     */
    public function markApproved(SpkluScrapeRaw $row, ?int $linkedLocationId = null): SpkluScrapeRaw
    {
        $row->update([
            'status' => SpkluScrapeRaw::STATUS_APPROVED,
            'linked_spklu_location_id' => $linkedLocationId,
        ]);

        return $row->fresh();
    }

    public function reject(SpkluScrapeRaw $row): void
    {
        $row->update(['status' => SpkluScrapeRaw::STATUS_REJECTED]);
    }
}
