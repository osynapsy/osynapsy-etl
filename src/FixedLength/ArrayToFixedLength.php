<?php
namespace Osynapsy\Etl\FixedLength;

class ArrayToFixedLength
{
    protected array $recordFormat; // Per memorizzare le definizioni dei campi

    /**
     * Costruttore.
     * @param array $recordTrack La definizione della struttura del record (campi, lunghezze, tipi, ecc.).
     * Esempio:
     * [
     * 'field1' => ['length' => 10, 'type' => 'string', 'padding' => ' ', 'paddingDirection' => STR_PAD_RIGHT, 'required' => true],
     * 'field2' => ['length' => 5,  'type' => 'integer', 'field' => 'source_field_name'], // 'field' mappa un nome diverso dal record di input
     * // ... altri campi
     * ]
     */
    public function __construct(array $recordTrack)
    {
        $this->recordFormat = $recordTrack;
    }

    /**
     * Genera una stringa a lunghezza fissa da un array di record.
     *
     * @param array $records Un array di array associativi, dove ogni array associativo rappresenta un record.
     * Esempio:
     * [
     * ['field1' => 'hello', 'source_field_name' => 123],
     * ['field1' => 'world', 'source_field_name' => 456],
     * ]
     * @return string La stringa generata a lunghezza fissa. Ogni record è tipicamente seguito da un carattere di newline.
     * @throws \Exception Se un campo richiesto è mancante o un valore supera la sua lunghezza definita.
     */
    public function generate(array $records): string
    {
        if (empty($this->recordFormat)) {
            $this->raiseException('Il formato del record non è definito. Forniscilo nel costruttore.');
            // return ''; // Non dovrebbe essere raggiunto se raiseException lancia un'eccezione
        }

        if (empty($records)) {
            return ''; // Restituisce una stringa vuota se non ci sono dati
        }

        $outputString = '';
        foreach ($records as $record) {
            if (empty($record)) { // Salta i record vuoti nell'array di input
                continue;
            }
            $rowString = $this->createRecordString($record);
            $outputString .= $rowString;
        }
        return $outputString;
    }

    /**
     * Crea una singola riga di stringa formattata a lunghezza fissa da un record.
     *
     * @param array $recordData Un array associativo che rappresenta un singolo record.
     * @return string La stringa del record formattata, seguita da PHP_EOL.
     */
    protected function createRecordString(array $recordData): string
    {
        $result = '';
        foreach ($this->recordFormat as $fieldId => $rule) {
            // 'field' nella regola permette la mappatura da una chiave diversa in $recordData
            $sourceFieldName = empty($rule['field']) ? $fieldId : $rule['field'];

            if (!empty($rule['required']) && !array_key_exists($sourceFieldName, $recordData)) {
                $this->raiseException(sprintf('Campo richiesto "%s" (mappato da "%s") non trovato nel record: %s', $fieldId, $sourceFieldName, print_r($recordData, true)));
            }

            $value = array_key_exists($sourceFieldName, $recordData) ? $recordData[$sourceFieldName] : '';
            $result .= $this->formatField($value, $rule);
        }
        return $result . PHP_EOL; // Aggiunge un carattere di newline dopo ogni record
    }

    /**
     * Formatta il valore di un singolo campo in base alla regola fornita.
     *
     * @param mixed $rawValue Il valore grezzo del campo.
     * @param array $rule Le regole di formattazione per questo campo.
     * @return string La stringa del campo formattata.
     */
    protected function formatField($rawValue, array $rule): string
    {
        $this->validateRule($rule); // Assicura che 'length' sia presente e valida
        $value = $this->ruleAdjustment($rawValue, $rule); // Applica conversioni di tipo, padding di default ecc.
                                                        // $rule può essere modificata per riferimento qui

        $this->validateValueLength($value, $rule['length']); // Controlla la lunghezza dopo l'aggiustamento ma prima del padding

        // Assicura che le impostazioni di padding siano disponibili dopo che ruleAdjustment le ha potenzialmente modificate
        $paddingChar = $rule['padding'] ?? ' ';
        $paddingDirection = $rule['paddingDirection'] ?? STR_PAD_RIGHT;
        $length = $rule['length'];

        return $this->mbStringPad($value, $length, $paddingChar, $paddingDirection);
    }

    /**
     * Esegue il padding di una stringa a una certa lunghezza con un'altra stringa (sicuro per multi-byte).
     *
     * @param string $input La stringa da riempire.
     * @param int $padLength La lunghezza finale della stringa.
     * @param string $padString La stringa con cui eseguire il padding.
     * @param int $padStyle STR_PAD_RIGHT, STR_PAD_LEFT, o STR_PAD_BOTH.
     * @param string $encoding Codifica dei caratteri.
     * @return string La stringa con padding.
     * @throws \Exception se la stringa di padding è vuota.
     */
    public function mbStringPad(string $input, int $padLength, string $padString = " ", int $padStyle = STR_PAD_RIGHT, string $encoding = "UTF-8"): string
    {
        $inputLength = mb_strlen($input, $encoding);

        if ($inputLength >= $padLength) {
            return mb_substr($input, 0, $padLength, $encoding); // Tronca se più lunga
        }

        if (mb_strlen($padString, $encoding) === 0) {
            $this->raiseException("La stringa di padding non può essere vuota.");
        }

        $numPadChars = $padLength - $inputLength;
        $paddedString = '';

        switch ($padStyle) {
            case STR_PAD_LEFT:
                $paddedString = str_repeat($padString, ceil($numPadChars / mb_strlen($padString, $encoding)));
                return mb_substr($paddedString, 0, $numPadChars, $encoding) . $input;
            case STR_PAD_BOTH:
                $leftPadLength = floor($numPadChars / 2);
                $rightPadLength = ceil($numPadChars / 2);

                $leftPad = str_repeat($padString, ceil($leftPadLength / mb_strlen($padString, $encoding)));
                $paddedString .= mb_substr($leftPad, 0, $leftPadLength, $encoding);

                $paddedString .= $input;

                $rightPad = str_repeat($padString, ceil($rightPadLength / mb_strlen($padString, $encoding)));
                $paddedString .= mb_substr($rightPad, 0, $rightPadLength, $encoding);
                return $paddedString;
            case STR_PAD_RIGHT:
            default:
                $rightPad = str_repeat($padString, ceil($numPadChars / mb_strlen($padString, $encoding)));
                return $input . mb_substr($rightPad, 0, $numPadChars, $encoding);
        }
    }

    /**
     * Ottiene la lunghezza della stringa sicura per multi-byte.
     *
     * @param string $input La stringa.
     * @param string $encoding Codifica dei caratteri.
     * @return int La lunghezza della stringa.
     */
    public function mbStringLength(string $input, string $encoding = 'UTF-8'): int
    {
        return mb_strlen($input, $encoding);
    }

    /**
     * Lancia un'eccezione con il messaggio dato.
     *
     * @param string $message Il messaggio dell'eccezione.
     * @throws \Exception
     */
    protected function raiseException(string $message): void
    {
        throw new \Exception($message);
    }

    /**
     * Aggiusta il valore grezzo in base alle regole (conversione di tipo, carattere/direzione di padding di default).
     * L'array $rule può essere modificato per riferimento per impostare opzioni di padding di default.
     *
     * @param mixed $rawValue Il valore di input grezzo.
     * @param array &$rule La definizione della regola, passata per riferimento.
     * @return string Il valore aggiustato.
     */
    protected function ruleAdjustment($rawValue, array &$rule): string
    {
        $value = is_scalar($rawValue) ? (string) $rawValue : ''; // Assicura che sia una stringa o scalare
        $value = str_replace(["\n", "\t", "\r"], ' ', rtrim($value));

        // Imposta il carattere di padding di default se non specificato
        if (!array_key_exists('padding', $rule)) {
            $rule['padding'] = ' '; // Default a spazio
        }
        // Imposta la direzione di padding di default se non specificata
        if (!array_key_exists('paddingDirection', $rule)) {
            $rule['paddingDirection'] = STR_PAD_RIGHT;
        }
        // Imposta il tipo di default se non specificato
        if (!array_key_exists('type', $rule)) {
            $rule['type'] = 'string';
        }

        switch ($rule['type']) {
            case 'decimal':
                $rule['padding'] = $rule['padding'] ?? '0'; // Permetti override, ma default a 0 per numerici
                $rule['paddingDirection'] = $rule['paddingDirection'] ?? STR_PAD_LEFT;
                $decimals = $rule['decimals'] ?? 3; // Permetti di specificare il numero di decimali nella regola
                $numericValue = empty(trim($value)) ? 0.0 : (float)str_replace(',', '.', $value); // Gestisce la virgola come separatore decimale
                $value = str_replace('.', '', number_format($numericValue, $decimals, '.', ''));
                break;
            case 'integer':
                $rule['padding'] = $rule['padding'] ?? '0';
                $rule['paddingDirection'] = $rule['paddingDirection'] ?? STR_PAD_LEFT;
                $numericValue = empty(trim($value)) ? 0 : (float)str_replace(',', '.', $value);
                $value = (string) round($numericValue, 0);
                break;
            case 'money':
                $rule['padding'] = $rule['padding'] ?? '0';
                $rule['paddingDirection'] = $rule['paddingDirection'] ?? STR_PAD_LEFT;
                $decimals = $rule['decimals'] ?? 2; // La valuta ha tipicamente 2 decimali
                $numericValue = empty(trim($value)) ? 0.0 : (float)str_replace(',', '.', $value);
                $value = str_replace('.', '', number_format($numericValue, $decimals, '.', ''));
                break;
            case 'flag':
                $rule['padding'] = $rule['padding'] ?? ' '; // Il carattere di padding potrebbe non essere '0'
                $rule['length'] = $rule['length'] ?? 1; // Assicura che la lunghezza sia impostata, default 1 per i flag
                if (is_bool($rawValue)) {
                    $value = $rawValue ? 'Y' : 'N'; // Esempio: rappresentazione comune per booleani
                } else {
                     // Prende solo il primo carattere se la stringa è più lunga, dopo averla convertita a stringa
                    $value = mb_substr((string) $rawValue, 0, 1, "UTF-8");
                }
                break;
            case 'string':
            default:
                // Gestito dai default iniziali per padding e direzione
                break;
        }
        return $value;
    }

    /**
     * Valida che le parti essenziali di una regola siano presenti e corrette.
     *
     * @param array $rule La regola da validare.
     * @throws \Exception Se la regola non è valida (es. 'length' mancante o non intero positivo).
     */
    protected function validateRule(array $rule): void
    {
        if (!array_key_exists('length', $rule) || !is_int($rule['length']) || $rule['length'] <= 0) {
            $this->raiseException('La regola deve contenere un parametro "length" intero e positivo. Regola: ' . print_r($rule, true));
        }
    }

    /**
     * Valida se la lunghezza del valore (sicuro per multi-byte) supera la lunghezza massima consentita.
     * Questo controllo è tipicamente fatto *prima* del padding.
     *
     * @param string $value Il valore da controllare.
     * @param int $length La lunghezza massima consentita.
     * @throws \Exception Se il valore è troppo lungo.
     */
    protected function validateValueLength(string $value, int $length): void
    {
        $currentLength = $this->mbStringLength($value); // Usa il nome corretto del metodo
        if ($currentLength > $length) {
            $this->raiseException(sprintf(
                'La stringa "%s" è troppo lunga (%d caratteri). La lunghezza massima consentita è %d caratteri.',
                $value,
                $currentLength,
                $length
            ));
        }
    }
}
