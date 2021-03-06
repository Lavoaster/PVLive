<?php
namespace Modules\Admin\Controllers;

use \Entity\Station;
use \Entity\Station as Record;

class StationsController extends BaseController
{
    public function permissions()
    {
        return $this->acl->isAllowed('administer stations');
    }
    
    public function indexAction()
    {
        $records = $this->em->createQuery('SELECT s, ss FROM Entity\Station s LEFT JOIN s.streams ss ORDER BY s.category ASC, s.weight ASC, s.id ASC')
            ->getArrayResult();

        $stations_by_category = array();
        $pending_stations = array();

        foreach($records as $station)
        {
            if ($station['is_active'] || $station['is_special'])
                $stations_by_category[$station['category']][] = $station;
            else
                $pending_stations[] = $station;
        }

        $this->view->categories = $stations_by_category;
        $this->view->pending_stations = $pending_stations;
    }
    
    public function editAction()
    {
        $form = new \DF\Form($this->current_module_config->forms->station);
        
        if ($this->hasParam('id'))
        {
            $id = (int)$this->getParam('id');
            $record = Record::find($id);
            $form->setDefaults($record->toArray(FALSE, TRUE));
        }

        if($_POST && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if (!($record instanceof Record))
                $record = new Record;

            $files = $form->processFiles('stations');

            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            
            $record->fromArray($data);
            $record->save();

            // Clear station cache.
            \DF\Cache::remove('stations');

            $this->alert('Changes saved.', 'green');
            return $this->redirectFromHere(array('action' => 'index', 'id' => NULL));
        }

        $this->renderForm($form, 'edit', 'Edit Record');
    }
    
    public function deleteAction()
    {
        $record = Record::find($this->getParam('id'));
        if ($record)
            $record->delete();
            
        $this->alert('Record deleted.', 'green');
        $this->redirectFromHere(array('action' => 'index', 'id' => NULL, 'csrf' => NULL));
    }
}