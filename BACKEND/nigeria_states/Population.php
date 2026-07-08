<?php 
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json'); 

include 'Data.php';
global $statesData;

class Population {
    private array $data;

    public function __construct(array $statesData)
    {
        $this->data = $statesData;
    }    

    public function filter(int $lowest, int $highest){
        $filteredStates = [];

        foreach ($this->data as $state) {
            if($state['population'] >= $lowest && $state['population'] <= $highest){
                $filteredStates[] = [
                    'name' => $state['name'],
                    'population' => $state['population']
                ];
            }
        }
        echo json_encode($filteredStates);
    }
}

// Safely capture integers, validating that parameters are both set AND not empty strings
$lowest = (isset($_GET['lowest']) && $_GET['lowest'] !== '') ? (int)$_GET['lowest'] : 0;
$highest = (isset($_GET['highest']) && $_GET['highest'] !== '') ? (int)$_GET['highest'] : 1000000000; 

$populationFilter = new Population($statesData);
$populationFilter->filter($lowest, $highest);
?>