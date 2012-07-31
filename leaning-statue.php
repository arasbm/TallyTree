<?php
/*
Copyright (C) 2011  TallyTree Authors

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>
<?php
/**
 * LeaningStatue: This class is responsible for the visualization widget
 * used by TallyTree plugin for a two sided question.
 */
class LeaningStatue {
    var $left_answer;
    var $right_answer;

    public function __construct(
                    $id,
                    $question,
                    $answers_string,
                    $theme_name,
                    $statue_path,
                    $background_image,
                    $css_path,
                    $width,
                    $height,
                    $min_votes_before_fall,
                    $fall_percentage) {

        $this->id = $id;
        $this->question = $question;
        $this->theme_name = $theme_name;
        $this->statue_path = $statue_path;
        $this->background_image = $background_image;
        $this->css_path = $css_path;
        $this->width = $width;
        $this->height = $height;
        $this->min_votes_before_fall = $min_votes_before_fall;
        $this->fall_percentage = $fall_percentage;

        $this->extract_answers($answers_string);
    }

    /**
     * Extract answers from string seperated by %
     */
    private function extract_answers($answers_string) {
        $answers = explode('%', $answers_string);
        $this->left_answer = $answers[0];
        $this->right_answer = $answers[1];
    }

    public function get_html_code() {
        //TODO: finish me
        return '
        <h2>' . $this->question . '</h2>
        <canvas id="viewport" width="'. $this->width .
        '" height="' .  $this->height . '"></canvas>';
    }

    public function get_left_answer() {
        return $this->left_answer;
    }

    public function get_right_answer() {
        return $this->right_answer;
    }
}
?>
