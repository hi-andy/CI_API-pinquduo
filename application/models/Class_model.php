<?PHP

class Class_model extends CI_Model
{
	public function getNextLevel($parents = array(), $son = array(), $operation = array(), $parents_key, $son_key, $key1, $key2, $add_all = null)
	{

		foreach ($operation as $k=>$v)
		{
			$arr = array();
			foreach ($son as $k1=>$v1)
			{
				if($v["{$parents_key}"] == $v1["{$son_key}"])
				{
					$arr[$k1] = $son[$k1];
				}
			}
			$parents['cat2'][$k]['cat3'] = $arr;
			if($add_all==1)
			{
				array_unshift($parents["{$key1}"][$k]["{$key2}"], array('id' => '0', 'name' => '全部'));
			}
		}

		return $parents;
	}
}