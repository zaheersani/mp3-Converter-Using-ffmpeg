## Introduction
This is a PHP based script Convert MP3 Files to 128Kbps with the help of ffmpeg tool. While converting, you can modify mp3 file details, such as, Cover Image, Title, Artist, Album, Year, Genre.

To use this utility, you need to setup few things:

## Step 1: Install ffmpeg
Access server through SSH and install ffmpeg
$ sudo apt-get install ffmpeg

## Step 2: Run the Application
Extract archive or upload source code via FTP and access index.html
index.html is the first contact and your software should work

## Step 3: Add Cron job
Follow the instructions to add a crob job if you have only SSH access
https://support.cloudways.com/how-to-add-cron-via-ssh/

Skip above step if you have control panel access and add Cron Job from there.

script for cron job is located under 'cronjob' directory named as 'job.sh'

## Step 4: Modify 'job.sh'
Modify 'job.sh' and replace paths according to server and hosting

