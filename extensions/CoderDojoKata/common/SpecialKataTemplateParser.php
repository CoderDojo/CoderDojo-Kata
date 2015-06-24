<?php
	class SpecialKataTemplateParser {
		
		const XPATH_QUERY = '//root/template';
		const XPATH_TEMPLATE_ID = 'title';
		const XPATH_TEMPLATE_TAG_ID = 'part/name';
		const XPATH_TEMPLATE_TAG_VALUE = 'part/value';
		const PARSER_MAX_INCLUDE_SIZE = 67108864;
		
		private static function initTemplateDataArray($parserTags, $defaultValue){
			$data = array();
			
			foreach ($parserTags as &$value)
				$data[$value] = $defaultValue;
			
			return $data;
		}
		
		public static function parseTemplate($wgParser, $context, $wikiPage, $parserTags, $dataDefaultValue){
			
			$doc = new DOMDocument(); 
			$data = self::initTemplateDataArray($parserTags, $dataDefaultValue);
			$options = null; $xml = null;
			$namespaceText = str_replace("_", " ", $wikiPage->getTitle()->getNsText());
		
			$options = ParserOptions::newFromContext($context);
			$options->setRemoveComments(false);
			$options->setTidy(true);
			$options->setMaxIncludeSize(self::PARSER_MAX_INCLUDE_SIZE);
		
			$wgParser->startExternalParse($wikiPage->getTitle(), $options, OT_PREPROCESS);
			$dom = $wgParser->preprocessToDom($wikiPage->getText());
			$xml = (method_exists( $dom, 'saveXML')) ? $dom->saveXML() : $dom->__toString();
		
			$doc->preserveWhiteSpace = false; 
			$doc->loadXML($xml);
			$xpath = new DOMXPath($doc);
		
			$templates = $xpath->query(self::XPATH_QUERY, $doc);
			foreach ($templates as $template) {
				$index = 0;
				$data = self::initTemplateDataArray($parserTags, $dataDefaultValue);
					
				$templateNodeName = $xpath->query(self::XPATH_TEMPLATE_ID, $template);
				if($templateNodeName->length < 1)
					continue;
		
				$templateFullName = $templateNodeName->item(0)->nodeValue;
				$templateFullName = explode(":", $templateFullName);
					
				if(!is_array($templateFullName) || empty($templateFullName) || $templateFullName[0] != $namespaceText)
					continue;
					
				$names = $xpath->query( self::XPATH_TEMPLATE_TAG_ID, $template );
				$values = $xpath->query( self::XPATH_TEMPLATE_TAG_VALUE, $template );
		
				foreach ($names as $name) {
					if(in_array(trim($name->nodeValue), $parserTags))
						$data[trim($name->nodeValue)] = $values->item($index)->nodeValue;
						
					$index++;
				}
					
				$data = array_map('trim', $data);
			}
		
			return $data;
		}
		
		
		public static function parseWikiText($wgParser, $context, $wikiText){
			$options = ParserOptions::newFromContext($context);
			$options->setRemoveComments(false);
			$options->setTidy(true);
			$options->setMaxIncludeSize(self::PARSER_MAX_INCLUDE_SIZE);
		
			$wgParser->startExternalParse(null, $options, OT_PREPROCESS);
			$text = $wgParser->preprocess($wikiText, null, $options);
		
			return $text;
		}
	}
?>