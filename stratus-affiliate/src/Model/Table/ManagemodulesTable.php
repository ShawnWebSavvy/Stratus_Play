<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ManagemodulesTable extends Table
{
 public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('fc_managemodules');
       
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
 
      
    }
    
  
}
?>
