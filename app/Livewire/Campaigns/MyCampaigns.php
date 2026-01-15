<?php

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyCampaigns extends Component
{
    #[Computed]
    public function campaigns()
    {
        return Campaign::where('user_id', Auth::id())
            ->withCount(['dataPoints', 'surveyZones'])
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.campaigns.my-campaigns');
    }
}
