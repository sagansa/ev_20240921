<?php

namespace App\Observers;

use App\Models\ChargerLocation;
use App\Models\ContributorProfile;
use App\Models\LocationAuditLog;

class ChargerLocationObserver
{
    /**
     * Handle the ChargerLocation "created" event.
     */
    public function created(ChargerLocation $chargerLocation): void
    {
        // Update contributor profile if this is a community submission
        if ($chargerLocation->data_source === 'community') {
            $contributorProfile = ContributorProfile::firstOrCreate(
                ['user_id' => $chargerLocation->user_id],
                [
                    'credibility_score' => 0,
                    'total_contributions' => 0,
                    'approved_contributions' => 0,
                    'rejected_contributions' => 0,
                ]
            );
            
            $contributorProfile->increment('total_contributions');
            
            // If verification_status is 'pending_verification', no credibility change yet
            // If verification_status is 'community_verified', increment credibility
            if ($chargerLocation->verification_status === 'community_verified') {
                $contributorProfile->increment('approved_contributions');
                $contributorProfile->increment('credibility_score', 10);
                
                // Check if user should be promoted to trusted
                if ($contributorProfile->approved_contributions >= 5 && 
                    $contributorProfile->credibility_score >= 50) {
                    $contributorProfile->update(['is_trusted' => true]);
                    
                    // Assign trust level based on credibility score
                    if ($contributorProfile->credibility_score >= 100) {
                        $contributorProfile->update(['trust_level' => 'expert']);
                    } elseif ($contributorProfile->credibility_score >= 75) {
                        $contributorProfile->update(['trust_level' => 'trusted']);
                    } elseif ($contributorProfile->credibility_score >= 25) {
                        $contributorProfile->update(['trust_level' => 'contributor']);
                    }
                }
            } elseif ($chargerLocation->verification_status === 'rejected') {
                $contributorProfile->increment('rejected_contributions');
                $contributorProfile->decrement('credibility_score', 5);
            }
        }
        
        // Log the creation in the audit log
        LocationAuditLog::create([
            'location_id' => $chargerLocation->id,
            'user_id' => $chargerLocation->user_id,
            'action' => 'create',
            'new_data' => $chargerLocation->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the ChargerLocation "updated" event.
     */
    public function updated(ChargerLocation $chargerLocation): void
    {
        // Check if verification status changed
        if ($chargerLocation->isDirty('verification_status')) {
            $oldStatus = $chargerLocation->getOriginal('verification_status');
            $newStatus = $chargerLocation->verification_status;
            
            // Update contributor profile based on verification status change
            if ($chargerLocation->data_source === 'community' && $chargerLocation->user_id) {
                $contributorProfile = ContributorProfile::firstOrCreate(
                    ['user_id' => $chargerLocation->user_id],
                    [
                        'credibility_score' => 0,
                        'total_contributions' => 0,
                        'approved_contributions' => 0,
                        'rejected_contributions' => 0,
                    ]
                );
                
                // Handle transitions from pending to verified/rejected
                if ($oldStatus === 'pending_verification') {
                    if ($newStatus === 'community_verified') {
                        $contributorProfile->increment('approved_contributions');
                        $contributorProfile->increment('credibility_score', 10);
                        
                        // Check if user should be promoted to trusted
                        if ($contributorProfile->approved_contributions >= 5 && 
                            $contributorProfile->credibility_score >= 50) {
                            $contributorProfile->update(['is_trusted' => true]);
                            
                            // Assign trust level based on credibility score
                            if ($contributorProfile->credibility_score >= 100) {
                                $contributorProfile->update(['trust_level' => 'expert']);
                            } elseif ($contributorProfile->credibility_score >= 75) {
                                $contributorProfile->update(['trust_level' => 'trusted']);
                            } elseif ($contributorProfile->credibility_score >= 25) {
                                $contributorProfile->update(['trust_level' => 'contributor']);
                            }
                        }
                    } elseif ($newStatus === 'rejected') {
                        $contributorProfile->increment('rejected_contributions');
                        $contributorProfile->decrement('credibility_score', 5);
                    }
                }
                // Handle transitions from verified to rejected (reverse credibility)
                elseif ($oldStatus === 'community_verified' && $newStatus === 'rejected') {
                    $contributorProfile->decrement('approved_contributions');
                    $contributorProfile->decrement('credibility_score', 10);
                    
                    // Check if demotion is needed
                    if ($contributorProfile->credibility_score < 25) {
                        $contributorProfile->update(['is_trusted' => false, 'trust_level' => 'novice']);
                    } elseif ($contributorProfile->credibility_score < 75 && $contributorProfile->trust_level === 'trusted') {
                        $contributorProfile->update(['trust_level' => 'contributor']);
                    } elseif ($contributorProfile->credibility_score < 100 && $contributorProfile->trust_level === 'expert') {
                        $contributorProfile->update(['trust_level' => 'trusted']);
                    }
                }
                // Handle transitions from rejected to verified (restore credibility)
                elseif ($oldStatus === 'rejected' && $newStatus === 'community_verified') {
                    $contributorProfile->decrement('rejected_contributions');
                    $contributorProfile->increment('credibility_score', 10);
                    
                    // Check if promotion is needed
                    if ($contributorProfile->approved_contributions >= 5 && 
                        $contributorProfile->credibility_score >= 50) {
                        $contributorProfile->update(['is_trusted' => true]);
                        
                        if ($contributorProfile->credibility_score >= 100) {
                            $contributorProfile->update(['trust_level' => 'expert']);
                        } elseif ($contributorProfile->credibility_score >= 75) {
                            $contributorProfile->update(['trust_level' => 'trusted']);
                        } elseif ($contributorProfile->credibility_score >= 25) {
                            $contributorProfile->update(['trust_level' => 'contributor']);
                        }
                    }
                }
            }
        }
        
        // Log the update in the audit log
        LocationAuditLog::create([
            'location_id' => $chargerLocation->id,
            'user_id' => auth()->id(), // Use current authenticated user (admin)
            'action' => 'update',
            'old_data' => $chargerLocation->getOriginal(),
            'new_data' => $chargerLocation->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the ChargerLocation "deleted" event.
     */
    public function deleted(ChargerLocation $chargerLocation): void
    {
        // Log the deletion in the audit log
        LocationAuditLog::create([
            'location_id' => $chargerLocation->id,
            'user_id' => auth()->id(), // Use current authenticated user
            'action' => 'delete',
            'old_data' => $chargerLocation->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the ChargerLocation "restored" event.
     */
    public function restored(ChargerLocation $chargerLocation): void
    {
        // Log the restoration in the audit log
        LocationAuditLog::create([
            'location_id' => $chargerLocation->id,
            'user_id' => auth()->id(), // Use current authenticated user
            'action' => 'restore',
            'new_data' => $chargerLocation->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the ChargerLocation "force deleted" event.
     */
    public function forceDeleted(ChargerLocation $chargerLocation): void
    {
        // Log the force deletion in the audit log
        LocationAuditLog::create([
            'location_id' => $chargerLocation->id,
            'user_id' => auth()->id(), // Use current authenticated user
            'action' => 'force_delete',
            'old_data' => $chargerLocation->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
