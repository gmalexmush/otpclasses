<?php

namespace Otpclasses\Otpclasses;

use Otpclasses\Otpclasses\LogUtilities;

class XMLUtility extends LogUtilities
{

    public function __construct( $logName = '/xmlutilities.log', $cuteIdentifier = 'XmlUtilities.', $cuteModule = true, $withOldLog = true  ) {

        parent::__construct( $logName, $cuteIdentifier, $cuteModule, $withOldLog );

    }

    public function __destruct() {

        parent::__destruct();
    }
    //
    // простой но быстрый вариант
    //

    public function arrayToXml( $array, $xml ) {

        foreach( $array as $key => $value ) {

            if( is_array( $value ) ) {
                //если элемент число, то добавляем префикс, с цифр не может начинаться
                if(!is_numeric($key)) {
                    $subnode = $xml->addChild("$key");
                    self::arrayToXml($value, $subnode);
                } else {
                    $subnode = $xml->addChild("item$key");
                    self::arrayToXml($value, $subnode);
                }
            }
            else {
                if(!is_numeric($key)) {
                    $xml->addChild("$key","$value");
                } else {
                    $xml->addChild("item$key", htmlspecialchars("$value") );
                }
            }
        }

        return $xml->asXML();
    }



    public function xmlToArraySimple( $fileName )
    {

        $xmlBox     = [];

        if( file_exists( $fileName ) && is_readable( $fileName ) ) {

//          $this->logging_debug( "xml file: " . $fileName );

            $handle     = fopen( $fileName, "r" );
            $contents   = fread( $handle, filesize( $fileName ) );
            fclose( $handle );
            //
//          $this->logging_debug( "xml string: " . $contents );
            //
            try {
                $xmlObject = simplexml_load_string($contents);

                if( $xmlObject !== false ) {

//                  $this->logging_debug("xml object:");
//                  $this->logging_debug($xmlObject);

                    $json = json_encode($xmlObject);
                    $xmlBox = json_decode($json, true);

                } else {
                    $xmlBox = [];

                    $this->logging_debug("Ошибка преобразования XML в объект:");

                    foreach( libxml_get_errors() as $error ) {

                        $this->logging_debug( $error->message );
                    }
                }

            } catch( \Exception $e ) {

                $this->logging_debug( 'Exception:' );
                $this->logging_debug( $e->getMessage() );
            }
        }

        return( $xmlBox );
    }




    public function SimpleXmlToArray( $fileName )
    //
    // тоже самое, что и xmlToArraySimple, только еще проще
    //
    {
        $xmlBox     = [];

        if( file_exists( $fileName ) && is_readable( $fileName ) ) {

//          $this->logging_debug( "xml file: " . $fileName );
            //
            try {
                $xmlObject = simplexml_load_file( $fileName );

                if( $xmlObject !== false ) {

//                  $this->logging_debug("xml object:");
//                  $this->logging_debug($xmlObject);

                    $json = json_encode($xmlObject);
                    $xmlBox = json_decode($json, true);

                } else {
                    $xmlBox = [];

                    $this->logging_debug("Ошибка преобразования XML в объект:");

                    foreach( libxml_get_errors() as $error ) {

                        $this->logging_debug( $error->message );
                    }
                }

            } catch( \Exception $e ) {

                $this->logging_debug( 'Exception:' );
                $this->logging_debug( $e->getMessage() );
            }
        } else {

            $this->logging_debug("XML файл не найден по указанному пути: " . $fileName );
        }

        return( $xmlBox );
    }






    public function xmlToArray( $fileName, $attributes = 1 )
    {

        $xmlBox     = [];

        if( file_exists( $fileName ) && is_readable( $fileName ) ) {

            $handle     = fopen( $fileName, "r" );
            $contents   = fread( $handle, filesize( $fileName ) );
            fclose( $handle );
            //
            $xmlBox     = $this->xml2Array( $contents, $attributes );
            //
        }

        return( $xmlBox );
    }




    public function xml2Array( $contents, $get_attributes=1 )
    {
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }
        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
        xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
        xml_parse_into_struct( $parser, $contents, $xml_values );
        xml_parser_free( $parser );

        if(!$xml_values) return;//Hmm...

        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array;

        //Go through the tags.
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = '';
            if($get_attributes) {//The second argument of the function decides this.
                $result = array();
                if(isset($value)) $result['value'] = $value;

                //Set the attributes too.
                if(isset($attributes)) {
                    foreach($attributes as $attr => $val) {
                        if($get_attributes == 1) $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'

                        // TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too

                    }
                }
            } elseif(isset($value)) {
                $result = $value;
            }

            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;

                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    $current = &$current[$tag];

                } else { //There was another element with the same tag name
                    if(isset($current[$tag][0])) {
                        array_push($current[$tag], $result);
                    } else {
                        $current[$tag] = array($current[$tag],$result);
                    }
                    $last = count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }

            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;

                } else { //If taken, put all things inside a list(array)
                    if((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array...
                            or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
                        array_push($current[$tag],$result); // ...push the new element into that array.
                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                    }
                }

            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return( $xml_array );
    }


/*
    function xml2array($contents, $get_attributes=1, $priority = 'tag')
    //
    // метод взят от сюда: https://gist.github.com/lorenzoaiello/8189351
    // в доке PHP пишут, что это лучшее решение!
    //
    {
        if(!$contents) return array();

        if(!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if(!$xml_values) return;//Hmm...

        //Initializations
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference

        //Go through the tags.
        $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
        foreach($xml_values as $data) {
            unset($attributes,$value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = array();
            $attributes_data = array();

            if(isset($value)) {
                if($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too.
            if(isset($attributes) and $get_attributes) {
                foreach($attributes as $attr => $val) {
                    if($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }

            //See tag status and do the needed.
            if($type == "open") {//The starting of the tag '<tag>'
                $parent[$level-1] = &$current;
                if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag.'_'.$level] = 1;

                    $current = &$current[$tag];

                } else { //There was another element with the same tag name

                    if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
                        $repeated_tag_index[$tag.'_'.$level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag.'_'.$level] = 2;

                        if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                            unset($current[$tag.'_attr']);
                        }

                    }
                    $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
                    $current = &$current[$tag][$last_item_index];
                }

            } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if(!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag.'_'.$level] = 1;
                    if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

                } else { //If taken, put all things inside a list(array)
                    if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

                        if($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag.'_'.$level]++;

                    } else { //If it is not an array...
                        $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag.'_'.$level] = 1;
                        if($priority == 'tag' and $get_attributes) {
                            if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                $current[$tag]['0_attr'] = $current[$tag.'_attr'];
                                unset($current[$tag.'_attr']);
                            }

                            if($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
                    }
                }

            } elseif($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level-1];
            }
        }

        return($xml_array);
    }
*/


}

