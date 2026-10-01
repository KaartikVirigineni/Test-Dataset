import 'package:graphql_flutter/graphql_flutter.dart';

class GraphQLService {
  static const String _baseUrl = 'http://localhost:8000/graphql';

  static ValueNotifier<GraphQLClient> getClient(String? token) {
    final httpLink = HttpLink(_baseUrl);

    final authLink = AuthLink(
      getToken: () async => token != null ? 'Bearer $token' : null,
    );

    final link = authLink.concat(httpLink);

    return ValueNotifier(
      GraphQLClient(
        link: link,
        cache: GraphQLCache(store: InMemoryStore()),
      ),
    );
  }

  static const String getProjectsQuery = r'''
    query GetProjects {
      projects {
        id
        name
        description
        apiKey
        createdAt
      }
    }
  ''';

  static const String createProjectMutation = r'''
    mutation CreateProject($name: String!, $description: String!) {
      createProject(name: $name, description: $description) {
        id
        name
        description
        apiKey
        createdAt
      }
    }
  ''';

  static const String deleteProjectMutation = r'''
    mutation DeleteProject($id: Int!) {
      deleteProject(id: $id)
    }
  ''';

  static const String getResourcesQuery = r'''
    query GetResources {
      resources {
        id
        title
        content
        category
        createdAt
      }
    }
  ''';
}