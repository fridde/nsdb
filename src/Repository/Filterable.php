<?php /** @noinspection PhpPrivateFieldCanBeLocalVariableInspection */


namespace App\Repository;


use App\Utils\Attributes\FilterMethod;
use App\Utils\ExtendedCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Symfony\Component\HttpFoundation\Request;

trait Filterable
{
    private Criteria $criteria;

    /** The keys correspond to the keys used in the json-request, the values to the function
     * used in this repository
     */
    public array $filterTranslator = [];

    public function getCriteria(): Criteria
    {
        $this->setCriteriaIfNotSet();
        return $this->criteria;
    }

    public function setCriteriaIfNotSet(): void
    {
        $this->criteria ??= Criteria::create();
    }

    public function resetCriteria(): void
    {
        $this->criteria = Criteria::create();
    }

    public function applyFilterFunctions(array $filter = []): Criteria
    {
        $this->setCriteriaIfNotSet();
        array_walk($filter, fn($val, $key) => $this->{$key}($val));
        
        return $this->criteria;
    }

    public function translateFilterFromRequest(Request $request): array
    {
        $potentialFilter = self::standardizeFilterParameters($request->query->all());
        $filterTranslator = $this->getFilterTranslator();

        $actualFilter = array_intersect_key($potentialFilter, $filterTranslator);
        // ensures that no superfluous values are in the translator
        $filterFunctions = array_intersect_key($filterTranslator, $potentialFilter);

        return array_combine($filterFunctions, $actualFilter);
    }

    public function setFilterTranslator(): void
    {
        $reflector = new \ReflectionClass(self::class);
        $methods = $reflector->getMethods();
        foreach($methods as $method){
            $attributes = $method->getAttributes(FilterMethod::class);
            if(!empty($attributes)){
                $attribute = reset($attributes);
                $filterMethod = $attribute->newInstance();
                /** @var FilterMethod $filterMethod  */
                $filterMethod->setMethodName($method->getShortName());
                $this->filterTranslator[$filterMethod->getJsonKey()] = $filterMethod->getMethodName();
            }
        }
    }

    public function getFilterTranslator(): array
    {
        if(empty($this->filterTranslator)){
            $this->setFilterTranslator();
        }
        return $this->filterTranslator;
    }

    public function addAndFilter(string $fieldName, $value, string $operator = Comparison::EQ): self
    {
        $this->setCriteriaIfNotSet();
        $comp = $this->createComparison($fieldName, $operator, $value);
        $this->criteria->andWhere($comp);

        return $this;
    }

    public function createComparison(string $fieldName, string $operator, $value): Comparison
    {
        return new Comparison($fieldName, $operator, $value);
    }

    public function addOrder(string $fieldName, string $direction = Criteria::ASC): self
    {
        return $this->addMultipleOrders([$fieldName => $direction]);
    }

    public function addMultipleOrders(array $orders = []): self
    {
        $sortedOrders = [];
        foreach($orders as $field => $direction){
            if(in_array($direction, [Criteria::ASC, Criteria::DESC], true)) {
                $sortedOrders[$field] = $direction;
            } else {
                $sortedOrders[$direction] = Criteria::ASC; // default value
            }
        }

        $this->setCriteriaIfNotSet();
        $this->criteria->orderBy($sortedOrders);

        return $this;
    }

    public function limitBy(int $limit): self
    {
        $this->setCriteriaIfNotSet();
        $this->criteria->setMaxResults($limit);

        return $this;
    }



    public static function standardizeFilterParameters(array $filter = []): array
    {
        $simpleValues = array_filter($filter, fn($k) => is_numeric($k), ARRAY_FILTER_USE_KEY);
        $keyedValues = array_filter($filter, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);

        $return = array_map(function($v){
            return match($v){
                0, '0', 'false' => false,
                1, '1', 'true' => true
            };
        } , $keyedValues);

        return $return + array_fill_keys($simpleValues, true);
    }

    public function getMatching(): ExtendedCollection
    {
        $results = $this->matching($this->getCriteria());
        $this->resetCriteria();

        return new ExtendedCollection($results->toArray());
    }

    #[FilterMethod('active')]
    public function isActive(bool $status = true): self
    {
        if(property_exists($this->getClassName(), 'Status')){
            return $this->addAndFilter('Status', $status);
        }

        return $this;
    }

}