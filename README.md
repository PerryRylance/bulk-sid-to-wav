# Bulk SID to FLAC
Uses SID2WAV and FFMPEG to render SID to FLAC in bulk, including meta-data.

## Requirements
- Windows operating system (for SID2WAV).
- You must have `ffmpeg` available on your path.

## Installation
`composer install`

## Running
`composer run render <pattern> -- [--song-length-database=<file>]`

Please note the song length database must be in the "old" format - See HVSC documentation.