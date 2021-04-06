<?php

/**
 * Description of PreloadText
 *
 * @author komooo
 */
class PreloadText extends \LimeSurvey\PluginManager\PluginBase
{
    static protected $description = 'PreloadText';
    static protected $name = 'PreloadText';
    private $result;
    private $node;
    private $type;
    private $sid;
    private $gip;
    private $qid;
    private $token;
    private $question;
    static protected $preloadText = "Letzte Woche haben Sie folgendes angegeben:<br />";

    protected $settings = [
        'url' => [
            'type' => 'string',
            'label' => 'URL'
        ]
    ];

    public function init()
    {
        $this->subscribe('beforeQuestionRender');
    }


    public function logger($msg, $context = 'Debug in Console')
    {
        $output  = 'console.info(\'' . $context . ':\');';
        $output .= 'console.log(' . json_encode($msg) . ');';
        $output  = sprintf('<script>%s</script>', $output);

        echo $output;
    }


    public function beforeQuestionRender()
    {
        // get the event
        $oEvent = $this->getEvent();

        // get importand informations
        $this->type = $oEvent -> get('type');
        $this->sid = $oEvent->get('surveyId');
        $this->gip = $oEvent->get('gid');
        $this->qid = $oEvent -> get('qid');
        $this->token = Yii::app()->getRequest()->getParam('token');
        $this->question= Question::model()->findByAttributes(['qid' => $this->qid]);
        $this->logger($this->question->getOrderedAnswers());

        //$this->logger($this->sid."X".$this->gip."X".$this->qid);
        $this->logger($this->type);

        //$this->logger($question);
        //$this->result = $this->getLastAnswers($this->sid, $this->token);

        $this->createHelpNode();
        $query = $this->getQuery();

        foreach($query as $row)
        {
            //$this->logger($row);
            switch ($this->type)
            {
                //case "A": // ARRAY OF 5 POINT CHOICE QUESTIONS
                //case "B": // ARRAY OF 10 POINT CHOICE QUESTIONS
                //case "C": // ARRAY OF YES\No\gT("Uncertain") QUESTIONS
                //case "D": //D - Date
                //case "E": // ARRAY OF Increase/Same/Decrease QUESTIONS
                case "F": // FlEXIBLE ARRAY
                    $this->writeTypeF($row);
                    break;
                //case "G": // - Gender
                //case "H": // ARRAY (By Column)
                //case "I": //- Language Switch
                case "K": // Multiple Numerical
                    $this->writeTypeK($row);
                    break;
                case "L": // - List (Radio)
                    $this->writeTypeL($row);
                    break;
                case "M": //M - Multiple choice
                    $this->writeTypeM($row);
                    break;
                case "N": //N - Numerical input
                    $this->writeTypeN($row);
                    break;
                //case "O": //- List With Comment
                //case "P": //P - Multiple choice with comments
                //case "Q": // Multiple Short Text
                //case "R": //RANKING
                //case "S": // Short free text
                //case "T": // Long free text
                //case "U": // Huge free text
                //case "X": //This is a boilerplate question and it has no business in this script
                case "Y": //- Yes/No
                    $this->writeTypeY($row);
                    break;
                //case ";": //ARRAY (Multi Flex) (Text)
                case ":": //ARRAY (Multi Flex) (Numbers)
                    $this->writeTypeD($row);
                    break;
                //case "!": // - List (Dropdown)
                //case "1": // MULTI SCALE
                //case "5": //- 5 Point Choice
                default:  //Default settings
                    break;

            } //end switch
        }

    }

    private function createHelpNode()
    {
        $toWrite = "var helpText = document.createElement('div');";
        $toWrite .= "helpText.setAttribute('id','helptext-for-question-{$this->qid}');";
        $toWrite .= "helpText.innerHTML = 'Die Antworten der Vorwoche waren:<br />';";
        $toWrite .= "document.getElementById('question{$this->qid}').getElementsByClassName('question-valid-container').item(0).appendChild(helpText);";
        Yii::app()->clientScript->registerScript($this->qid,$toWrite,CClientScript::POS_END);
    }

    private function writeToHelp($id,$result)
    {
        $toWrite = "document.getElementById('helptext-for-question-{$this->qid}').innerHTML +=  '{$result} <br />';";
        $this->logger($toWrite);
        Yii::app()->clientScript->registerScript($id,$toWrite,CClientScript::POS_END);
    }


    public function writeTypeF($row)
    {
        $columnName = "{$row['sid']}X{$row['gid']}X{$row['parent_qid']}{$row['title']}";
        $result = $this->getLastAnswers($columnName);
        $this->writeToHelp($columnName,$result[$columnName]);
    }

    public function writeTypeK($row)
    {
        $columnName = "{$row['sid']}X{$row['gid']}X{$row['parent_qid']}{$row['title']}";
        $result = $this->getLastAnswers($columnName);

        if(!empty($result[$columnName]))
        {
            $this->logger($result[$columnName]);
            $this->writeToHelp($columnName,$row['question'].": ".$result[$columnName]);
        }
    }

    public function writeTypeL($row)
    {
        $columnName = "{$row['sid']}X{$row['gid']}X{$row['qid']}";
        $result = $this->getLastAnswers($columnName);
        $answers= $this->question->getOrderedAnswers();

        if(strpos($result[$columnName],"-oth-")!==false)
        {
            $cat = $this->getLastAnswers($columnName."other")[$columnName."other"];
            $this->writeToHelp($columnName, "Ja: ".$cat);
        }
        else
        {
            foreach ($answers as $answer)
            {
                if($answer["code"] == $result[$columnName])
                {
                    $this->writeToHelp($columnName,$answer["answer"]);
                }
            }
        }
    }

    public function writeTypeM($row)
    {
        $columnName = "{$row['sid']}X{$row['gid']}X{$row['parent_qid']}{$row['title']}";
        $result = $this->getLastAnswers($columnName);
        if(!empty($result[$columnName]))
        {
            $this->writeToHelp($columnName,$row['question']);
        }
    }

    public function writeTypeN($row)
    {
        $columnName = "{$row['sid']}X{$row['gid']}X{$row['qid']}";
        $result = $this->getLastAnswers($columnName);
        $this->writeToHelp($columnName,$result[$columnName]);
    }

    public function writeTypeY($row)
    {
        $columnName = "{$row['sid']}X{$row['gid']}X{$row['qid']}";
        $result = $this->getLastAnswers($columnName);
        if(!empty($result[$columnName]))
        {
            if($result[$columnName]=="Y")
            {
                $this->writeToHelp($columnName,"Ja");
            }
            else{
                $this->writeToHelp($columnName,"Nein");
            }
        }
    }

    public function writeTypeD($row)
    {
        $answerCategory = $this->getResponseCatForD();
        foreach ($answerCategory as $category)
        {
            $columnName = "{$row['sid']}X{$row['gid']}X{$row['parent_qid']}{$row['title']}_{$category['title']}";
            $result = $this->getLastAnswers($columnName);

            if(!empty($result[$columnName]))
            {
                $this->writeToHelp($columnName,$category["question"]." - ".$row['question'].": ".$result[$columnName]);
            }

        }
    }

    private function getLastAnswers($column)
    {
        $query = Yii::app()->getDb()
            ->createCommand()
            ->select("{$column}")
            ->from("lime_survey_{$this->sid}")
            ->where("token = '{$this->token}'")
            ->order("submitdate desc")
            ->queryRow();

        //$this->logger($query);
        return $query;
    }

    public function getQuery():array
    {
        if($this->type=="F" or $this->type=="K" or $this->type=="M")
        {
            return Question::model()->getQuestionsForStatistics('sid, gid, qid, parent_qid, title, question', "sid = '{$this->sid}' and gid = '$this->gip' and parent_qid = '$this->qid' and title like 'SQ%'", 'question_order');
        }
        elseif ($this->type==":")
        {
            return Question::model()->getQuestionsForStatistics('sid, gid, qid, parent_qid, title, question', "parent_qid={$this->qid} AND scale_id = 0", 'question_order');
        }
        else
        {
            return Question::model()->getQuestionsForStatistics('sid, gid, qid, parent_qid, title, question', "sid = '{$this->sid}' and gid = '{$this->gip}' and qid = '{$this->qid}'", 'question_order');
        }
    }

    public function getResponseCatForD():array
    {
        if($this->type==":")
        {
            return Question::model()->getQuestionsForStatistics('sid, gid, qid, parent_qid, title, question', "parent_qid={$this->qid} AND scale_id = 1", 'question_order');
        }
        return array();
    }

}
