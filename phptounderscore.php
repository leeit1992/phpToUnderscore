 <?php
/**
 * Package: PhpConvert
 * Author: HaLe
 * Version: 1.1
 * Description: Convert php to js underscore
 * Website: http://oneplus.com/
 */

class PhpConver{

	protected $rawRegex = array( '<\?php', '\?>' );

	protected $rawEchoReplace = array( array('echo $','echo','print $','print'),array('','','','') );

	protected $regex = array(
		'If' 		=> '/(if(.*):(.*))|(if(.*){(.+))/s',
		'Foreach'	=> '/(foreach((.*)as(.*)){)|(foreach((.*)as(.*)):)/s',
		'Endforeach' => '/(endforeach)/s',
		'For' 		=> '/for((.*)):(.*)|for((.*)){(.*)/s',
		'End' 		=> '/(endif)|(})|(endfor)/s',
		'Wphelp'	=> '/_e(.*)|__(.*)|esc_attr(.*)/s'
	);

	public function __construct( $value, $return = false, $file = null ){
		$classExtend = array();

		foreach(get_declared_classes() as $class)
	    {
	        if(is_subclass_of($class, 'PhpConver'))
	        {
	        	$classExtend[] = $class;
		        $cl = new $class;
		        $cl->compile($value,$return,$file);
		        $this->regexExtend = $cl->regexExtend;
	        }
	    }
	    $this->compile($value,$return,$file);   
	}

	public function compile( $value, $return = false, $file = null ){
		$value = $this->compileRawEchos($value);
		$value = $this->compileHtmlToJs($value);
		
		if($return == false && $file != null){
			if(file_exists($file)){
				file_put_contents($file,$value);
			}else{
				echo "<p style=\"color:red;\">File <b>{$file}</b> not found.</p>";
			}		
		}else{
			echo stripslashes($value);
		}
	}

	public function compileRawEchos( $value ){

		$pattern  = sprintf( '/(%s)(.+?)(%s)/s', $this->rawRegex[0], $this->rawRegex[1] );
		$callback = function ( $matches ) {

			if( $this->compileTypeToken( $matches[2], 'echo' ) == true ){

				return '<%= ' . trim( $this->compileConvertSyntax( $this->compileEchoDefaults( $matches[2] ) ) ) . ' %>';
				
			}else{

				$matches[2] = $this->compileConvertSyntax( $this->compileEchoDefaults( $matches[2] ) );
				$matches[2] = $this->compileStatements( $matches[2] );

				return '<% ' . $matches[2] . ' %>';
			}
			
        };
        return preg_replace_callback($pattern, $callback, $value);
	}

	public function compileHtmlToJs($value){
		$html = explode("\n",$value);
		$htmlCompile = array();

		for ($i=0; $i < count($html); $i++) { 
			if($i != count($html)-1){
				$htmlCompile[] = $html[$i]."\ ";
			}else{
				$htmlCompile[] = $html[$i];
			}
		}
		return implode("\n",$htmlCompile);
	}
	
	

	/**
     * Compile the default values for the echo statement.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEchoDefaults($value){	 
    	
    	$value = $this->complieIsset($value);

    	$value = str_replace($this->rawEchoReplace[0],$this->rawEchoReplace[1],$value);
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }

    /**
     * Compile the Isset into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    public function complieIsset($value){

    	$pattern = '/((.)isset\((.*?)\))|((.)empty\((.*?)\))/i';
    	$callback = function($matches){
    		$newMatches = array();
    		foreach ($matches as $key => $value) {
    			if(!empty($value)){
    				$newMatches[] = $value;
    			}
    		}
    		
    		if($newMatches[2] == '!'){
    			if(strpos($newMatches[0], 'empty')){
    				$not = '!';
    			}else{
    				$not = '=';
    			}
    			
    		}else{
    			if(strpos($newMatches[0], 'empty')){
    				$not = '=';
    			}else{
    				$not = '!';
    			}
    		}
    		if($newMatches[2] == '('){
    			return "((typeof ".$newMatches[3]." {$not}= \"undefined\")";
    		}else{
    			return "(typeof ".$newMatches[3]." {$not}= \"undefined\")";
    		}
    	};
    	return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * 
     * Compiled php syntax into the syntax js
     * @param  string  $value
     * @return string
     */
	protected function compileConvertSyntax($value){

	    $value = str_split($value,1); $js='';
	    
	    $str = '';                                                                                     
	    $strs = array('\'','`','"');                                                                   
	    $nums = array('0','1','2','3','4','5','6','7','8','9');                                     
	    $wsps = array(chr(9),chr(10),chr(13),chr(32));                                               
	    foreach( $value as $n => $c ){

	        $p = isset($value[$n-1]) ? $value[$n-1] : '';
	        $f = isset($value[$n+1]) ? $value[$n+1] : '';

	        if( $str != '' && $str != $c ){
		         $js .= $c; 
		         continue; 
		    }                                       
	        // if($c == "'"){
	        //  	$js.='"'; 
	        //  	continue;      
	        // }  
	        if( $str =='' && in_array($c,$strs)){ 
	        	$str =$c; 
	        	$js .=$c; 
	        	continue; 
	        }                    
	        if( $str != '' && $str == $c){ 
	        	$str =''; 
	        	$js .=$c; 
	        	continue; 
	        }                              
	        // else, it is inside code
	        if($c=='$'){
	        	continue;   
	        }                                                               
	        if($c == ':' && $f == ':'){ 
	        	$js.='.'; 
	        	continue; 
	        }                                       
	        if($p == ':' && $c == ':'){
	        	continue;  
	        }                                                 
	        if($c == '-' && $f == '>'){ 
	        	$js.='.'; continue;  
	        }                                        
	        if($p == '-' && $c == '>'){ 
	        	continue;        
	        }    
	        if($c == ';'){ 
	        	continue;       
	        }   
	        if($c == ' '){ 
	         	$js.=' '; 
	         	continue;      
	        }                                     
	        if($c == '.' && (!in_array($p,$nums) || !in_array($f,$nums))){
	         	$js.='+'; continue; 
	     	}    
	        if(in_array($c,$wsps)){
	        	continue;   
	        }                                                   
	        $js.=$c;
	    }
	    return $js;
	}

	protected function compileTypeToken($value,$type){
		if($type == 'echo'){
			if(!preg_match('/{(.*)(print(.*))/i', $value)){
				$pattern = '/^(\s+)(echo(.*))|(print(.*))/i';
				if(preg_match($pattern, $value)){
				 	return true;
				}else{
					return false;
				}
			}
			
		}	
	}

	/**
     * Compile the If into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
	public function compileIf($value){
    	preg_match_all('/if(.*){(.*)}/i', $value, $mIfCecho);
    	if(isset($mIfCecho[0][0])){
    		return "if".$mIfCecho[1][0]."{ %> <%= ".$this->compileConvertSyntax($mIfCecho[2][0])." %> <% }";
    	}else{
    		$pattern = $this->regex['If'];
	    	$callback = function($matches){
	    		$complieIf = array_values(array_filter($matches));
	    		return "if".$complieIf[2]."{ ".(isset($complieIf[3]) ? $complieIf[3] : '');
	    	};
	    	return preg_replace_callback($pattern, $callback, $value);
    	}
		
	}

	/**
     * Compile the Foreach into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
	public function compileForeach($value){
		$pattern = $this->regex['Foreach'];
    	$callback = function($matches){
    		$matches = array_values(array_filter($matches));
    		$matchesEach = str_replace('(','',$matches[3]);
    		$matchesKeyVal = explode('=>',$matches[4]);

    		if(isset($matchesKeyVal[1])){

    			$_key = trim($matchesKeyVal[0]);
    			$_val = trim(str_replace(')','',$matchesKeyVal[1]));

    			return "_.each({$matchesEach},function({$_val},{$_key}){";

    		}else{

    			$_val = trim(str_replace(')','',$matchesKeyVal[0]));
    			return "_.each({$matchesEach},function({$_val}){";

    		}
    	};
    	return preg_replace_callback($pattern, $callback, $value);
	}

	/**
     * Compile the End Foreach into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
	public function compileEndforeach($value){
		$pattern = $this->regex['Endforeach'];
    	$callback = function($matches){
    		return "})";
    	};
    	return preg_replace_callback($pattern, $callback, $value);
	}

	/**
     * Compile the Endfor,Endif into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
	public function compileEnd($value){
		$pattern = $this->regex['End'];
    	$callback = function($matches){
    		return "}";
    	};
    	return preg_replace_callback($pattern, $callback, $value);
	}

	/**
     * Compile the for into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
	public function compileFor($value){
		$pattern = $this->regex['For'];
		$callback = function($matches){
			return "for".$matches[1]."{ ".(isset($matches[3]) ? $matches[3] : '');
		};
		return preg_replace_callback($pattern, $callback, $value);
	}

	public function compileWphelp($value){
		$pattern = $this->regex['Wphelp'];
		$callback = function($matches){
			echo "<pre>";
			var_dump($matches);
		};
		return preg_replace_callback($pattern, $callback, $value);
	}

	/**
     * Compile the statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileStatements($expression){
    	$classExtend = array();

 		foreach(get_declared_classes() as $class)
	    {
	        if(is_subclass_of($class, 'PhpConver'))
	        {
	        	$classExtend[] = $class;
		        $cl = new $class;
		        foreach ($cl->regex as $key => $regex) {
		    		if(preg_match($regex,$expression)){
			    		return $cl->{"compile{$key}"}($expression);
			    	}
		    	}
	        }
	    }
	    if(empty($classExtend)){
	    	foreach ($this->regex as $key => $regex) {
	    		if(preg_match($regex,$expression)){
		    		return $this->{"compile{$key}"}($expression);
		    	}
	    	}
	    }
    }
}

