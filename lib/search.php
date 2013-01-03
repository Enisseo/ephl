<?php
/**
 * Provides search functions.
 *
 * @author Enisseo
 */
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'mysql.php');

define('SEARCH_TOLERANCE_STRICT', 0);
define('SEARCH_TOLERANCE_FUZZY', 1);
//define('SEARCH_TOLERANCE_LOOSE', 2);

/**
 * The Search base class.
 */
class Search
{
	protected $mysql;
	protected $tables = array(
		'document' => 'search_document',
		'word' => 'search_word',
		'index' => 'search_index',
	);

	public function __construct($mysql = null)
	{
		$this->mysql = $mysql;
	}

	public function createDocument($data)
	{
		return $this->mysql->insert()->into($this->tables['document'])
			->set($data)->executeAndGetInsertedId();
	}

	public function indexDocument($documentId, $content)
	{
		$words = $this->getWords($content);
		$uniqueWords = array_unique($words);
		$wordsIds = $this->mysql->select()->fields('word', 'id')->from($this->tables['word'])
			->where('`word` IN :words')->fetchKeyValue(array(':words' => $uniqueWords));
		foreach ($uniqueWords as $word)
		{
			if (!isset($wordsIds[$word]))
			{
				$soundex = soundex($word);
				$letter = substr($soundex, 0, 1);
				$figure = substr($soundex, 1);
				if (!$figure)
				{
					$figure = 0;
				}
				$wordId = $this->mysql->insert()->into($this->tables['word'])
					->set(array(
						'word' => $word,
						'soundex_letter' => $letter,
						'soundex_figure' => $figure,
					))->executeAndGetInsertedId();
				$wordsIds[$word] = $wordId;
			}
		}
		$this->mysql->delete()->from($this->tables['index'])
			->where('`document_id` = :documentId')->with(':documentId', $documentId)->execute();
		$position = 0;
		foreach ($words as $word)
		{
			$wordId = $wordsIds[$word];
			$this->mysql->insert()->into($this->tables['index'])
				->set(array(
					'document_id' => $documentId,
					'word_id' => $wordId,
					'weight' => 1,
					'position' => $position,
				))->execute();
			$position++;
		}
	}

	public function getWords($text)
	{
		setlocale(LC_CTYPE, 'utf-8');
		$text = str_replace(array('\'', '"', '’', '.', ',', '?', ';', ':', '!'), ' ', $text);
		$text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
		$words = preg_split('/\pZ+/', trim($text));
		$cleanWords = array();
		foreach ($words as $word)
		{
			if (strlen($word))
			{
				$word = strtolower(preg_replace('/\W/', '', $word));
				$cleanWords[] = $word;
			}
		}
		return $cleanWords;
	}

	public function searchQuery($query, $tolerance = SEARCH_TOLERANCE_STRICT)
	{
		$documents = array();
		$words = $this->getWords($query);
		$searchWords = $this->searchWords($words, $tolerance);

		if (!empty($searchWords))
		{
			$results = $this->mysql->select()->from($this->tables['index'])
				->where('`word_id` IN :wordsIds')
				->orderBy('document_id')->orderBy('position', 'ASC')
				->fetchArray(array(':wordsIds' => array_keys($searchWords)));
			$documentsWeights = array();
			$wordPosition = array();
			foreach ($words as $pos => $word)
			{
				foreach ($searchWords as $searchWordId => $searchWord)
				{
					if ($searchWord['for'] == $word)
					{
						$wordPosition[$searchWordId] = $pos;
					}
				}
			}
			$previousDiffPosition = 0;
			foreach ($results as $res)
			{
				if (!isset($documentsWeights[$res['document_id']]))
				{
					$documentsWeights[$res['document_id']] = array();
					$previousDiffPosition = 0;
				}
				$documentsWeights[$res['document_id']][]
					= $res['weight'] * $searchWords[$res['word_id']]['weight']
						/ (abs($res['position'] - $wordPosition[$res['word_id']] - $previousDiffPosition) + 1);
				$previousDiffPosition = $res['position'] - $wordPosition[$res['word_id']];
			}
			foreach ($documentsWeights as $documentId => $documentWeights)
			{
				$documents[$documentId] = array_sum($documentWeights);
			}
			arsort($documents);
		}
		return array_keys($documents);
	}

	public function searchWords($words, $tolerance = SEARCH_TOLERANCE_STRICT)
	{
		$wordsData = array();
		switch ($tolerance)
		{
			case SEARCH_TOLERANCE_STRICT:
				$wordsData = $this->mysql->select()->fields('*', '1 AS `weight`', '`word` AS `for`')
					->from($this->tables['word'])->where('`word` IN :words')
					->fetchArrayByKey('id', array(':words' => $words));
				break;
			case SEARCH_TOLERANCE_FUZZY:
				foreach ($words as $word)
				{
					$tempWordsData = $this->mysql->select()->fields('*')
						->from($this->tables['word'])->where('`word` LIKE :word')
						->fetchArrayByKey('id', array(':word' => $word . '%'));
					foreach ($tempWordsData as $wordId => $wordData)
					{
						$wordData['weight'] = strlen($word) / strlen($wordData['word']);
						$wordData['for'] = $word;
						$wordsData[$wordId] = $wordData;
					}
				}
				break;
		}
		return $wordsData;
	}
}
