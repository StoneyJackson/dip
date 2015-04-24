#!/bin/bash
branch="$1"
git --git-dir="$branch/.git" pull
