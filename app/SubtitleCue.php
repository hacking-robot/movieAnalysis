<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\MovieSubtitle;
use DB;

class SubtitleCue extends Model
{
    public $timestamps = false;

    protected $words = array();

    /*
     * Find the words in the cue and add the frequence
     *
     */
    protected function findWordsFrequences() {

        // filter content
        $content = $this->caption;
        $content = str_replace('’',"'",$content);
        $content = str_replace('--'," ",$content);

        // calculate mean line frequence
        $lines = preg_split('/[\.\n]+/',$content);

        $frequences = array();
        foreach($lines as $line) {
            $matches = str_word_count($line,1,'’1234567890');
            foreach($matches as $word) {
                $word = self::filterWord($word);
                $word = strtolower($word);
                if(!self::isWord($word)){
                    continue;
                }
                if(!isset($frequences[strtolower($word)])) {
                    $frequences[strtolower($word)] = 0;
                }
                $frequences[strtolower($word)]++;
            }
        }

        return $frequences;
    }

    public function addWordsFrequences() {
        $frequences = $this->findWordsFrequences();

        arsort($frequences);

        // write the words to database
        foreach($frequences as $word => $frequence) {
            $wordObj=Word::where(array('value'=>strtolower($word),'language'=>$this->subtitle->language))->orderBy('frequence_spoken','desc')->first();
            if(empty($wordObj)) {
                $wordObj = new Word();
                $wordObj->value = strtolower($word);
                $wordObj->language = $this->subtitle->language;
                $wordObj->frequence_movie = 0;
                $wordObj->frequence_book = 0;
            } else {
                // if word existing try to match the lemma if one existing
                if($wordObj->lemma_word_id != null) {
                    // adds the frequence before changing word
                    $wordObj->frequence_movie += $frequence;
                    $wordObj->save();

                    $wordObj=Word::where(array('lemma_word_id'=>$wordObj->lemma_word_id))->orderBy('frequence_spoken','desc')->first();
                }
            }
                $wordObj->frequence_movie += $frequence;
                $wordObj->save();
        }

        // adds the subtitle_words to database
        $this->addSubtitleWords();
    }

    protected function addSubtitleWords() {
        $frequences = $this->findWordsFrequences();

        // write the words to database
        foreach($frequences as $word => $frequence) {
            $wordObj=Word::where(array('value'=>strtolower($word),'language'=>$this->subtitle->language))->orderBy('frequence_spoken','desc')->first();
            if(empty($wordObj)) {
                continue;
            }

            // if word existing try to match the lemma if one existing
            if($wordObj->lemma_word_id != null) {
                $wordObj=Word::where(array('lemma_word_id'=>$wordObj->lemma_word_id))->first();
            }

            $subWordObj=SubtitleWord::where(array('word_id'=>$wordObj->id,'subtitle_id'=>$this->subtitle_id))->first();
            if(empty($subWordObj)) {
                $subWordObj = new SubtitleWord();
                $subWordObj->subtitle_id= $this->subtitle->id;
                $subWordObj->word_id = $wordObj->id;
                $subWordObj->frequence= 0;
            }

            $subWordObj->frequence += $frequence;
            $subWordObj->save();
        }

    }

    public function findCovering($coverPercent) {
        $wordsSubResult= $this->findWordsFrequences();
        $words= DB::select('SELECT * FROM words order by frequence_spoken desc');

        $wordsSub = array();
        $sumSub = 0;
        foreach($wordsSubResult as $word => $frequence) {
            $wordsSub[$word] = $frequence; 
            $sumSub += $frequence;
        }

        // find count to have to get 75% of the global content
        $percent_material = 0;

        $sum = 0;
        $n = 0;
        foreach($words as $word) {
            $n++;
            if(isset($wordsSub[$word->value])) {
                $sum += $wordsSub[$word->value];
                $percent_material = ($sum*100)/$sumSub;
                if($percent_material >= $coverPercent)
                   break;
            }
        }

        return $n;
    }

    public function computeScore() {



        return true;


        $sum_score = 0;

        // most frequent word
        $mostUsedWord = Word::where(array())->orderBy('frequence_spoken','desc')->take(1)->get()->first();     
        
        // all words in db
        $sum_words = DB::select('SELECT sum(frequence_spoken) as frequence FROM words')[0]->frequence;

        $words = Word::get20Words();         

        $percent20words = array();
        foreach($words as $word) {
           $percent20words[$word->id] = $word;
        }

        // compute score
        foreach($frequences as $word => $frequence) {
            $wordObj=Word::where(array('value'=>strtolower($word),'language'=>$this->subtitle->language))->orderBy('frequence_spoken','desc')->first();
            // we substracte to the most used word to reverse the result
            if(!isset($percent20words[$wordObj->id]))
                $sum_score += (($mostUsedWord->frequence*100)/$sum_words) - (($wordObj->frequence*100)/$sum_words);
        }

        // we calculate the difficuly / millisecond times the number of words (times a rate of 10 to reduce the float)
        return $this->score = $sum_score;
    }

    static function isWord($word) {

        $err = false;

        // one letter word
        if(strlen($word)==1) {
            if(!in_array($word,array('a','i','o','1','2','3','4','5','6','7','8','9','0')))
                $err = true;
        }
    
        // two letters word
        if(strlen($word)==2) {
            $twoLettersWords = array(
            'aa',
            'ab',
            'ad',
            'ag',
            'ah',
            'ai',
            'am',
            'an',
            'as',
            'at',
            'aw',
            'ax',
            'ay',
            'ba',
            'be',
            'bi',
            'bo',
            'by',
            'da',
            'do',
            'dy',
            'ee',
            'eh',
            'El',
            'em',
            'en',
            'er',
            'ex',
            'fa',
            'Ga',
            'gi',
            'go',
            'ha',
            'he',
            'hi',
            'ho',
            'id',
            'if',
            'in',
            'io',
            'is',
            'it',
            'ja',
            'jo',
            'Ju',
            'ka',
            'ki',
            'la',
            'li',
            'lo',
            'ma',
            'me',
            'mi',
            'mo',
            'mu',
            'my',
            'né',
            'no',
            'nu',
            'ob',
            'od',
            'of',
            'og',
            'oh',
            'oi',
            'OK',
            'om',
            'on',
            'op',
            'or',
            'os',
            'ou',
            'ow',
            'ox',
            'oy',
            'Oz',
            'pa',
            'pi',
            'po',
            'qi',
            'ra',
            're',
            'ri',
            'se',
            'si',
            'so',
            'ta',
            'te',
            'ti',
            'to',
            'uh',
            'um',
            'up',
            'us',
            'Wa',
            'we',
            'Wu',
            'xi',
            'xu',
            'ye',
            'Yi',
            'yo',
            'yu',);

            if(!in_array($word,$twoLettersWords))
                $err = true;
        }
    
        if( (preg_match('/[^\x20-\x7f]/', $word)))
            $err = true;

        return !$err;
    }

    static function filterWord($word) {
       $word = trim($word,"'"); 
       $word = trim($word,"-"); 

       return $word;
    }

    /**
     * Get the subtitle that owns the cue.
     */
    public function subtitle()
    {
        return $this->belongsTo('App\MovieSubtitle', 'subtitle_id');
    }
}
