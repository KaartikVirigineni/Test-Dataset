workers 2
threads 1, 6

bind 'tcp://0.0.0.0:4567'

preload_app!

on_worker_boot do
  require_relative 'lib/database'
end