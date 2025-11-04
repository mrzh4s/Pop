<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class NewPage extends BasePage {
    
    public function show($params) {

        $id = $params['id'] ?? null;
        $system = $params['system'] ?? null;

        if($system === 'Imohon') {
            $data = (object) [
                'imohon' => $this->getImohonData($id),
                'kiter' => $this->getDestData($id, $system)
            ];
        } else {
            $data = (object) [
                'v2' => $this->getV2Data($id),
                'kiter' => $this->getDestData($id, $system)
            ];
        }

        return view('applications.migrate.new', [
            'entryId' => $id,
            'system' => $system,
            'title' => 'Migrate Application',
            'data' => $data
        ]);
    }

    private function getImohonData($id) {
        
        $entries = (object) gf_entry($id);
        $quotation = (object) gf_entry($entries->{'89'});


        //convert string with comma to array
        $entries->{'87'} = explode(',', $entries->{'87'});

        $authority = [];
        foreach($entries->{'87'} as $key) {
            $authority[] =  (object) gf_entry($key);
        }

        $authority = $this->mergeAuthority((object) $authority);

        $data = (object) [
            'entry' => $entries,
            'quotation' => $quotation,
            'authority' => $authority
        ];

        return $data;
    }

    private function mergeAuthority($entries) {
    $merged = [];
    
    foreach ($entries as $entry) {
        // Create unique key based on authority name only
        // This will group all entries for the same authority together
        $authorityKey = trim($entry->{1});
        
        if (!isset($merged[$authorityKey])) {
            // First entry for this authority
            $merged[$authorityKey] = clone $entry;
            $merged[$authorityKey]->statuses = [$entry->{4}];
            $merged[$authorityKey]->first_status = $entry->{4};
            $merged[$authorityKey]->current_status = $entry->{4};
            $merged[$authorityKey]->last_updated = $entry->date_created;
        } else {
            // Merge subsequent entries for the same authority
            // Keep the most recent/complete data
            
            // Update dates if newer
            if (strtotime($entry->date_created) > strtotime($merged[$authorityKey]->last_updated)) {
                $merged[$authorityKey]->last_updated = $entry->date_created;
                $merged[$authorityKey]->current_status = $entry->{4}; // Update to latest status
                $merged[$authorityKey]->{4} = $entry->{4};
            }
            
            // Update fields if they have data and current doesn't
            if (!empty($entry->{2})) $merged[$authorityKey]->{2} = $entry->{2};
            if (!empty($entry->{3})) $merged[$authorityKey]->{3} = $entry->{3};
            if (!empty($entry->{6})) $merged[$authorityKey]->{6} = $entry->{6};
            if (!empty($entry->{7})) $merged[$authorityKey]->{7} = $entry->{7};
            if (!empty($entry->{8})) $merged[$authorityKey]->{8} = $entry->{8};
            if (!empty($entry->{10})) $merged[$authorityKey]->{10} = $entry->{10};
            if (!empty($entry->{11})) $merged[$authorityKey]->{11} = $entry->{11};
            if (!empty($entry->{12})) $merged[$authorityKey]->{12} = $entry->{12};
            
            // Track all statuses
            if (!in_array($entry->{4}, $merged[$authorityKey]->statuses)) {
                $merged[$authorityKey]->statuses[] = $entry->{4};
            }
        }
    }
    
    return array_values($merged);
}


    private function getV2Data($id) {

        $row = db_query(
        'source',
        'SELECT * FROM core.mig_flw_appl_entry WHERE id = :id',
        ['id' => $id])->fetch();

        return $row;
        
    }

    private function getDestData($id, $system) {

        $id = (string) $id;
        $system = strtolower($system);
        

        $row = db_query(
        'dest',
        'SELECT * FROM view_entry_migrates WHERE old_system_id = :id AND old_system = :system',
        ['id' => $id, 'system' => $system])->fetch();

        if ($row) {
            return $row;
        }

        return [];
    }
}