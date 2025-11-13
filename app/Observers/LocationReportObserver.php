<?php

namespace App\Observers;

use App\Models\LocationReport;
use App\Models\ContributorProfile;

class LocationReportObserver
{
    /**
     * Handle the LocationReport "created" event.
     */
    public function created(LocationReport $locationReport): void
    {
        // When a report is created, we don't immediately affect credibility
        // since the report hasn't been verified yet
    }

    /**
     * Handle the LocationReport "updated" event.
     */
    public function updated(LocationReport $locationReport): void
    {
        // Check if the status changed to approved/resolved or rejected
        if ($locationReport->isDirty('status')) {
            $oldStatus = $locationReport->getOriginal('status');
            $newStatus = $locationReport->status;
            
            if ($locationReport->reporter_id) {
                $contributorProfile = ContributorProfile::firstOrCreate(
                    ['user_id' => $locationReport->reporter_id],
                    [
                        'credibility_score' => 0,
                        'total_contributions' => 0,
                        'approved_contributions' => 0,
                        'rejected_contributions' => 0,
                    ]
                );
                
                // Handle transitions for reports
                if ($oldStatus === 'pending' && $newStatus === 'resolved') {
                    // Increase credibility for successful reports
                    $contributorProfile->increment('approved_contributions');
                    $contributorProfile->increment('credibility_score', 5);
                    
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
                } elseif ($oldStatus === 'pending' && $newStatus === 'rejected') {
                    // Decrease credibility for rejected reports
                    $contributorProfile->increment('rejected_contributions');
                    $contributorProfile->decrement('credibility_score', 3);
                    
                    // Check if demotion is needed
                    if ($contributorProfile->credibility_score < 25) {
                        $contributorProfile->update(['is_trusted' => false, 'trust_level' => 'novice']);
                    } elseif ($contributorProfile->credibility_score < 75 && $contributorProfile->trust_level === 'trusted') {
                        $contributorProfile->update(['trust_level' => 'contributor']);
                    } elseif ($contributorProfile->credibility_score < 100 && $contributorProfile->trust_level === 'expert') {
                        $contributorProfile->update(['trust_level' => 'trusted']);
                    }
                }
                // Handle transitions from rejected to resolved (restore credibility)
                elseif ($oldStatus === 'rejected' && $newStatus === 'resolved') {
                    $contributorProfile->decrement('rejected_contributions');
                    $contributorProfile->increment('credibility_score', 3);
                    
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
    }

    /**
     * Handle the LocationReport "deleted" event.
     */
    public function deleted(LocationReport $locationReport): void
    {
        //
    }

    /**
     * Handle the LocationReport "restored" event.
     */
    public function restored(LocationReport $locationReport): void
    {
        //
    }

    /**
     * Handle the LocationReport "force deleted" event.
     */
    public function forceDeleted(LocationReport $locationReport): void
    {
        //
    }
}