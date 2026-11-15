<title>Question Answers</title>

Question 1:
Is this approach secure? What should be done to make it more secure?

I would say that my approach has some secure features used, but I think I could do better.

For example, not write everything in 1 file and create different files for functions, like connecting to the service with cURL and storing functions that repeat in seperate files.

I have also made hard coded values not appear in the index.php file, espacially those that are needed for logging into the service and the IP address.


Question 2:
Why are all time values in UTC?

UTC is the Coordinated Universal Time that is used to regulate time zones and watches.

Unix does not handle timezones, if the time was needed to be time zone specific DateTimeImmutable class could be used with its DateTimeImmutable:format() formatting method to format date/time information. (Cited from [https://www.php.net/manual/en/function.date.php](https://www.php.net/manual/en/function.date.php))

Question 3:
How many ‘ŗ’s are there in the word ‘cranberries’?

This question confuses me, if it's meant for the letter 'r' than 2.
But if it is 'ŗ', which it most certainly is, then I am guessing this is to catch someone using AI.