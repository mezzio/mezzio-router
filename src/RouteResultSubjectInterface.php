<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Router;

/**
 * Aggregate and notify route result observers.
 *
 * A route result subject typically composes a router, and will then notify
 * observers of a route result returned by routing; the Application instance
 * is typically the subject.
 *
 * @since 1.1.0
 */
interface RouteResultSubjectInterface
{
    /**
     * Attach a route result observer.
     *
     * @param RouteResultObserverInterface $observer
     */
    public function attachRouteResultObserver(RouteResultObserverInterface $observer);

    /**
     * Detach a route result observer.
     *
     * If the observer was not previously attached, this is a no-op.
     *
     * @param RouteResultObserverInterface $observer
     */
    public function detachRouteResultObserver(RouteResultObserverInterface $observer);

    /**
     * Notify route result observers of a given route result.
     */
    public function notifyRouteResultObservers(RouteResult $result);
}
