# docx2txt
Convert DOCX files to text

## Usage

Simply call the file and pipe a file by STDIN or pass a filename.

Example:

    php docx2txt.php < test.docx

Or:

    php docx2txt.php "test.docx"

## Warnings

- This script requires full access to STDIN and to the temporary folder (`/tmp` on *NIX, `%temp%` on Windows).

- This won't work with DOC files!
