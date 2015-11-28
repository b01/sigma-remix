# Description
A rewrite of the PEAR module HTML_Template_Sigma written by Alexey Borzov and others.

## Why A Re-Write
I like how well Sigma integrates with HTML and the API is quite simple. However, its written as one big class, and can
be hard to modify; although I do not plan to modify it much. I finally decided to rewrite it to modernize it a bit and
see if I can squeeze a bit more performance out of it. The most compelling reason, which I finally gave into doing a
re-write wsa so that I can output the compiled templates as PHTML. Which should allow any PHP developer to easily
review/debug the compiled template.